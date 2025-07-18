<?php

namespace App\Controllers;

use App\Models\TournamentModel;
use App\Models\TeamModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;

class TournamentController extends BaseController
{
    private $session;
    private $http;

    // OAuth2 constants for Challonge API
    private const DOMAIN = "challonge.com";
    private const CLIENT_ID = "f7375c8b6fe777e703b91661fb78a6bf17f147f56416736dc56e4a0d8ccf70bc";
    private const CLIENT_SECRET = "fa3ed5d406444372a1d3b33789ee72fcba3f938c8543a7a4c9d80aab379741d9";
    private const REDIRECT_URI = "http://localhost:8080/tournaments/oauth-callback"; // use base_url for redirect URI
    private $redirectUri;
    private const VERIFY_SSL = false;

    private const OAUTH_ROOT_URL = "https://api." . self::DOMAIN;
    private const API_ROOT_URL = "https://api." . self::DOMAIN . "/v2";

    public function __construct()
    {
        helper(['url', 'form']);
        $this->session = \Config\Services::session();
        $this->http = \Config\Services::curlrequest([
            'verify' => self::VERIFY_SSL,
        ]);
        $this->session->start();
        $this->redirectUri = base_url('/tournaments/oauth-callback');
    }

    /**
     * Show admin login form
     */
    public function adminLogin()
    {
        return view('auth/admin_login');
    }

    /**
     * Process admin login
     */
    public function adminLoginPost()
    {
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        $userModel = new \App\Models\UserModel();
        $user = $userModel->where('username', $username)->where('role', 'admin')->first();

        if ($user && password_verify($password, $user['password_hash'])) {
            $this->session->set('is_admin', true);
            $this->session->set('user_id', $user['id']);
            $this->session->set('user_name', $user['username']);
            $this->session->set('role', $user['role']);
            // Automatically authorize Challonge OAuth2 for admin after login
            return $this->authorize();
        } else {
            return redirect()->back()->with('error', 'Invalid admin credentials.');
        }
    }

    /**
     * Show user login form
     */
    public function userLogin()
    {
        return view('auth/user_login');
    }

    /**
     * Process user login
     */
    public function userLoginPost()
    {
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        $userModel = new \App\Models\UserModel();
        $user = $userModel->where('username', $username)->where('role', 'user')->first();

        if ($user && password_verify($password, $user['password_hash'])) {
            $this->session->set('is_user', true);
            $this->session->set('user_id', $user['id']);
            $this->session->set('user_name', $user['username']);
            $this->session->set('role', 'user');
            return redirect()->to('/tournaments')->with('success', 'User login successful.');
        } else {
            return redirect()->back()->with('error', 'Invalid user credentials.');
        }
    }

    /**
     * Logout user or admin by destroying session
     */
    public function logout()
    {
        $this->session->destroy();
        return redirect()->to('/tournaments')->with('success', 'You have been logged out.');
    }

    /**
     * Mendapatkan Access Token dari Challonge API menggunakan client_credentials grant.
     * Ini digunakan untuk akses API yang tidak memerlukan otorisasi pengguna spesifik.
     */
    private function getAccessToken()
    {
        // Cek apakah token sudah ada di session dan masih valid (opsional, bisa ditambahkan logika expired)
        if ($this->session->has('challonge_client_access_token')) {
            return $this->session->get('challonge_client_access_token');
        }

        $response = $this->http->post(self::OAUTH_ROOT_URL . "/oauth/token", [
            'form_params' => [
                'client_id' => self::CLIENT_ID,
                'client_secret' => self::CLIENT_SECRET,
                'grant_type' => 'client_credentials',
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        if (isset($data['access_token'])) {
            $this->session->set('challonge_client_access_token', $data['access_token']);
            return $data['access_token'];
        }
        return null;
    }

    /**
     * Melakukan request ke Challonge API v2.
     * Menggunakan access token yang didapatkan dari getAccessToken().
     */
    private function requestChallonge($method, $uri, $data = [])
    {
        // Prefer user access token if available
        if ($this->session->has('challonge_access_token')) {
            $accessToken = $this->session->get('challonge_access_token');
        } else {
            $accessToken = $this->getAccessToken(); // fallback to client_credentials token
        }

        if (!$accessToken) {
            log_message('error', 'Challonge API: Access token not available.');
            return ['error' => 'Access token not available.'];
        }

        $options = [
            'headers' => [
                'Authorization' => "Bearer $accessToken",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json', // Penting untuk API v2
            ],
            'json' => $data
        ];

        try {
            $response = $this->http->request($method, self::API_ROOT_URL . $uri, $options);
            return json_decode($response->getBody(), true);
        } catch (\CodeIgniter\HTTP\Exceptions\HTTPException $e) {
            log_message('error', 'Challonge API Request Error: ' . $e->getMessage());
            return ['error' => 'Challonge API Request Failed: ' . $e->getMessage()];
        }
    }

    /**
     * Membuat turnamen baru di Challonge.
     */
    private function createChallongeTournament($name, $tournament_type, $description = null, $start_at = null, $url)
    {
        // Remove hardcoded overrides to use passed parameters
        $data = [
            'tournament' => [
                'name' => $name,
                'tournament_type' => $tournament_type, // Contoh: single_elimination, double_elimination, round_robin, swiss
                'description' => $description,
                'starts_at' => $start_at ? date('Y-m-d\TH:i:sP', strtotime($start_at)) : null, // Correct field name 'starts_at'
                'url' => $url, // URL unik
                'open_signup' => false,
                'private' => false,
                'hold_third_place_match' => false, // Added field as per API
                'pts_for_bye' => 1, // Added field as per API
                'pts_for_game_win' => 1, // Added field as per API
                'pts_for_match_win' => 3, // Added field as per API
                'pts_for_tie' => 1, // Added field as per API
                'swiss_rounds' => 0, // Added field as per API
                'rr_pts_for_game_win' => 1, // Added field as per API
                'rr_pts_for_match_win' => 3, // Added field as per API
                'rr_pts_for_tie' => 1, // Added field as per API
                'notify_users_when_matches_open' => true,
                'notify_users_when_the_tournament_ends' => true,
            ]
        ];
        return $this->requestChallonge('POST', '/tournaments', $data);
    }

    /**
     * Mengupdate turnamen di Challonge.
     */
    private function updateChallongeTournament($challonge_id, $name, $tournament_type, $description = null, $start_at = null)
    {
        $data = [
            'tournament' => [
                'name' => $name,
                'tournament_type' => $tournament_type,
                'description' => $description,
                'starts_at' => $start_at ? date('Y-m-d\TH:i:sP', strtotime($start_at)) : null,
            ]
        ];
        return $this->requestChallonge('PUT', '/tournaments/' . $challonge_id, $data);
    }

    /**
     * Menghapus turnamen di Challonge.
     */
    private function deleteChallongeTournament($challonge_id)
    {
        return $this->requestChallonge('DELETE', '/tournaments/' . $challonge_id);
    }

    /**
     * Menambahkan peserta (tim) ke turnamen di Challonge.
     */
    private function addChallongeParticipant($challonge_id, $team_name)
    {
        $data = [
            'participant' => [
                'name' => $team_name,
            ]
        ];
        return $this->requestChallonge('POST', '/tournaments/' . $challonge_id . '/participants', $data);
    }

    /**
     * Menghapus peserta (tim) dari turnamen di Challonge.
     */
    private function deleteChallongeParticipant($challonge_id, $participant_id)
    {
        return $this->requestChallonge('DELETE', '/tournaments/' . $challonge_id . '/participants/' . $participant_id);
    }

    // =================================================================
    // FUNGSI CRUD UNTUK TURNAMEN LOKAL
    // =================================================================

    public function index()
    {
        $tournamentModel = new TournamentModel();
        $data['tournaments'] = $tournamentModel->findAll();
        return view('tournaments/index', $data);
    }

    /**
     * Menampilkan form untuk membuat turnamen baru.
     */
    public function create()
    {
        return view('tournaments/create');
    }

    /**
     * Menyimpan turnamen baru ke database lokal dan Challonge.
     */
    public function store()
    {
        $tournamentModel = new TournamentModel();
        $name = $this->request->getPost('name');
        $game_name = $this->request->getPost('game_name');
        $description = $this->request->getPost('description');
        $tournament_type = $this->request->getPost('tournament_type');
        $start_at = $this->request->getPost('start_at');
        $url = strtolower(str_replace(' ', '_', $name)) . '_' . uniqid();

        $data = [
            'name' => $name,
            'challonge_id' => null,
            'challonge_url' => $url,
            'game_name' => $game_name,
            'description' => $description,
            'tournament_type' => $tournament_type,
            'start_at' => $start_at,
        ];

        // Integrasi dengan Challonge API: Buat turnamen di Challonge
        $challonge_data = $this->createChallongeTournament($name, $tournament_type, $description, $start_at, $url);

        log_message('debug', 'Challonge create tournament response: ' . json_encode($challonge_data));

        if (isset($challonge_data['data']['id'])) {
            $data['challonge_id'] = $challonge_data['data']['id'];
            $data['challonge_url'] = $challonge_data['data']['full_challonge_url'] ?? $url;
            $tournamentModel->insert($data);
            return redirect()->to('/tournaments')->with('success', 'Turnamen berhasil dibuat dan disinkronkan dengan Challonge.');
        } else {
            // Jika gagal membuat di Challonge, tetap simpan lokal atau berikan pesan error
            // $tournamentModel->insert($data); // Tetap simpan lokal
            log_message('error', 'Gagal membuat turnamen di Challonge: ' . json_encode($challonge_data));
            // dd(json_encode($challonge_data));
            return redirect()->to('/tournaments')->with('warning', 'Turnamen berhasil dibuat secara lokal, tetapi gagal disinkronkan dengan Challonge.');
        }
    }

    /**
     * Menampilkan form untuk mengedit turnamen.
     */
    public function edit($id)
    {
        $tournamentModel = new TournamentModel();
        $data['tournament'] = $tournamentModel->find($id);
        if (empty($data['tournament'])) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Turnamen tidak ditemukan');
        }
        return view('tournaments/edit', $data);
    }

    /**
     * Mengupdate data turnamen di database lokal dan Challonge.
     */
    public function update($id)
    {
        $tournamentModel = new TournamentModel();
        $existingTournament = $tournamentModel->find($id);

        if (empty($existingTournament)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Turnamen tidak ditemukan');
        }

        $name = $this->request->getPost('name');
        $game_name = $this->request->getPost('game_name');
        $description = $this->request->getPost('description');
        $tournament_type = $this->request->getPost('tournament_type');
        $start_at = $this->request->getPost('start_at');

        $data = [
            'name' => $name,
            'game_name' => $game_name,
            'description' => $description,
            'tournament_type' => $tournament_type,
            'start_at' => $start_at,
        ];

        $tournamentModel->update($id, $data);

        // Integrasi dengan Challonge API: Update turnamen di Challonge jika ada challonge_id
        if (!empty($existingTournament['challonge_id'])) {
            $challonge_data = $this->updateChallongeTournament(
                $existingTournament['challonge_id'],
                $name,
                $tournament_type,
                $description,
                $start_at
            );
            if (isset($challonge_data['tournament']['id'])) {
                return redirect()->to('/tournaments')->with('success', 'Turnamen berhasil diupdate dan disinkronkan dengan Challonge.');
            } else {
                log_message('error', 'Gagal mengupdate turnamen di Challonge: ' . json_encode($challonge_data));
                return redirect()->to('/tournaments')->with('warning', 'Turnamen berhasil diupdate secara lokal, tetapi gagal disinkronkan dengan Challonge.');
            }
        }

        return redirect()->to('/tournaments')->with('success', 'Turnamen berhasil diupdate.');
    }

    /**
     * Menghapus turnamen dari database lokal dan Challonge.
     */
    public function delete($id)
    {
        $tournamentModel = new TournamentModel();
        $tournament = $tournamentModel->find($id);

        if (empty($tournament)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Turnamen tidak ditemukan');
        }

        // Hapus dari Challonge jika ada challonge_id
        if (!empty($tournament['challonge_id'])) {
            $challonge_response = $this->deleteChallongeTournament($tournament['challonge_id']);
            if (isset($challonge_response['error'])) {
                log_message('error', 'Gagal menghapus turnamen dari Challonge: ' . json_encode($challonge_response));
                // Anda bisa memilih untuk tetap menghapus lokal atau tidak
                $tournamentModel->delete($id);
                return redirect()->to('/tournaments')->with('warning', 'Turnamen berhasil dihapus secara lokal, tetapi gagal dihapus dari Challonge.');
            }
        }

        $tournamentModel->delete($id);
        return redirect()->to('/tournaments')->with('success', 'Turnamen berhasil dihapus.');
    }

    // =================================================================
    // FUNGSI CRUD UNTUK TIM
    // =================================================================

    /**
     * Menampilkan semua tim dalam sebuah turnamen.
     */
    public function teams($tournament_id = null)
    {
        $tournamentModel = new TournamentModel();
        $teamModel = new TeamModel();
        $membershipRequestModel = new \App\Models\TeamMembershipRequestModel();

        if ($tournament_id === null) {
            $data['tournament'] = null;
            $data['teams'] = $teamModel->where('tournament_id', null)->findAll();
            $data['membership_requests'] = [];
        } else {
            $data['tournament'] = $tournamentModel->find($tournament_id);
            $data['teams'] = $teamModel->where('tournament_id', $tournament_id)->findAll();
            // Fix SQL syntax error by fetching team IDs first and using whereIn
            $db = \Config\Database::connect();
            $teamIds = $db->table('teams')->select('id')->where('tournament_id', $tournament_id)->get()->getResultArray();
            $teamIds = array_column($teamIds, 'id');
            if (empty($teamIds)) {
                $data['membership_requests'] = [];
            } else {
                $data['membership_requests'] = $membershipRequestModel->whereIn('team_id', $teamIds)->where('status', 'pending')->findAll();
            }

            if (empty($data['tournament'])) {
                throw new \CodeIgniter\Exceptions\PageNotFoundException('Turnamen tidak ditemukan');
            }
        }

        return view('tournaments/teams', $data);
    }

    /**
     * User dashboard showing their teams and membership requests
     */
    public function userDashboard()
    {
        $userId = $this->session->get('user_id');
        if (!$userId) {
            return redirect()->to('/tournaments/user-login')->with('error', 'Please login first.');
        }

        $teamModel = new TeamModel();
        $membershipRequestModel = new \App\Models\TeamMembershipRequestModel();

        // Get team IDs where user is a member (accepted)
        $db = \Config\Database::connect();
        $membershipTeamIds = $db->table('team_membership_requests')
            ->select('team_id')
            ->where('user_id', $userId)
            ->where('status', 'accepted')
            ->get()
            ->getResultArray();
        $membershipTeamIds = array_column($membershipTeamIds, 'team_id');

        // Get team IDs where user is the owner/creator
        $ownerTeamIds = $teamModel->where('owner_id', $userId)->select('id')->findColumn('id');

        // Combine and unique team IDs
        $allTeamIds = array_unique(array_merge($membershipTeamIds, $ownerTeamIds));

        if (empty($allTeamIds)) {
            $data['teams'] = [];
            $data['team_ids'] = [];
        } else {
            $data['teams'] = $teamModel->whereIn('id', $allTeamIds)->findAll();
            $data['team_ids'] = $allTeamIds;
        }
        $data['pending_requests'] = $membershipRequestModel->where('user_id', $userId)->where('status', 'pending')->findAll();

        return view('users/dashboard', $data);
    }

    /**
     * Show form for user to create a new team
     */
    public function createUserTeam()
    {
        return view('users/create_team');
    }

    /**
     * Process user team creation
     */
    public function storeUserTeam()
    {
        $userId = $this->session->get('user_id');
        if (!$userId) {
            return redirect()->to('/tournaments/user-login')->with('error', 'Please login first.');
        }

        $teamModel = new TeamModel();

        $name = $this->request->getPost('name');
        $tag = $this->request->getPost('tag');

        $data = [
            'name' => $name,
            'tag' => $tag,
            'user_id' => $userId,
            'owner_id' => $userId,
        ];

        $teamId = $teamModel->insert($data, true);

        if ($teamId) {
            // Automatically create membership request accepted for the creator as leader
            $membershipRequestModel = new \App\Models\TeamMembershipRequestModel();
            $membershipRequestModel->insert([
                'user_id' => $userId,
                'team_id' => $teamId,
                'status' => 'accepted',
                'role' => 'leader', // Add role field to indicate leader
            ]);

            // Check if tournament_id is provided in POST (for registration)
            $tournament_id = $this->request->getPost('tournament_id');
            if ($tournament_id) {
                $teamModel->update($teamId, ['tournament_id' => $tournament_id]);
            }

            return redirect()->to('/tournaments/teams/' . $teamId)->with('success', 'Team created successfully.');
        } else {
            return redirect()->back()->with('error', 'Failed to create team.');
        }
    }

    /**
     * Invite user to team by username (only leader can invite)
     */
    public function inviteUserToTeam($team_id)
    {
        $userId = $this->session->get('user_id');
        if (!$userId) {
            return redirect()->to('/tournaments/user-login')->with('error', 'Please login first.');
        }

        $membershipRequestModel = new \App\Models\TeamMembershipRequestModel();
        $teamModel = new TeamModel();

        $team = $teamModel->find($team_id);
        if (!$team) {
            return redirect()->back()->with('error', 'Team not found.');
        }

        // Check if current user is leader of the team
        $leaderRequest = $membershipRequestModel->where('team_id', $team_id)
            ->where('user_id', $userId)
            ->where('status', 'accepted')
            ->where('role', 'leader')
            ->first();

        if (!$leaderRequest) {
            return redirect()->back()->with('error', 'Only team leader can invite users.');
        }

        $usernameToInvite = $this->request->getPost('username');
        $userModel = new \App\Models\UserModel();
        $userToInvite = $userModel->where('username', $usernameToInvite)->first();

        if (!$userToInvite) {
            return redirect()->back()->with('error', 'User to invite not found.');
        }

        // Check if user already requested or is member
        $existingRequest = $membershipRequestModel->where('user_id', $userToInvite['id'])
            ->where('team_id', $team_id)
            ->first();

        if ($existingRequest) {
            return redirect()->back()->with('warning', 'User has already requested or is a member of the team.');
        }

        // Create accepted membership request for invited user
        $membershipRequestModel->insert([
            'user_id' => $userToInvite['id'],
            'team_id' => $team_id,
            'status' => 'accepted',
            'role' => 'member',
        ]);

        return redirect()->back()->with('success', 'User added successfully as a member.');
    }

    /**
     * Show user registration form
     */
    public function registerUser()
    {
        return view('auth/register_user');
    }

    /**
     * Process user registration
     */
    public function registerUserPost()
    {
        $userModel = new \App\Models\UserModel();

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $passwordConfirm = $this->request->getPost('password_confirm');

        if ($password !== $passwordConfirm) {
            return redirect()->back()->with('error', 'Password confirmation does not match.');
        }

        $existingUser = $userModel->where('username', $username)->first();
        if ($existingUser) {
            return redirect()->back()->with('error', 'Username already exists.');
        }

        $data = [
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'user',
        ];

        $userModel->insert($data);
        return redirect()->to('/tournaments/user-login')->with('success', 'Registration successful. Please login.');
    }

    /**
     * User requests to join a team
     */
    public function requestJoinTeam($team_id)
    {
        $userId = $this->session->get('user_id');
        if (!$userId) {
            return redirect()->to('/tournaments/user-login')->with('error', 'Please login first.');
        }

        $membershipRequestModel = new \App\Models\TeamMembershipRequestModel();

        // Check if already requested or member
        $existingRequest = $membershipRequestModel->where('user_id', $userId)->where('team_id', $team_id)->first();
        if ($existingRequest) {
            return redirect()->back()->with('warning', 'You have already requested to join this team.');
        }

        $data = [
            'user_id' => $userId,
            'team_id' => $team_id,
            'status' => 'pending',
        ];

        $membershipRequestModel->insert($data);
        return redirect()->back()->with('success', 'Join request sent. Waiting for approval.');
    }

    /**
     * Approve a team membership request
     */
    public function approveJoinRequest($request_id)
    {
        $membershipRequestModel = new \App\Models\TeamMembershipRequestModel();
        $request = $membershipRequestModel->find($request_id);

        if (!$request) {
            return redirect()->back()->with('error', 'Request not found.');
        }

        $membershipRequestModel->update($request_id, ['status' => 'accepted']);
        return redirect()->back()->with('success', 'Request approved.');
    }

    /**
     * Reject a team membership request
     */
    public function rejectJoinRequest($request_id)
    {
        $membershipRequestModel = new \App\Models\TeamMembershipRequestModel();
        $request = $membershipRequestModel->find($request_id);

        if (!$request) {
            return redirect()->back()->with('error', 'Request not found.');
        }

        $membershipRequestModel->update($request_id, ['status' => 'rejected']);
        return redirect()->back()->with('success', 'Request rejected.');
    }

    /**
     * Menyimpan tim baru ke database lokal dan Challonge.
     */
    public function storeTeam($tournament_id = null)
    {
        $tournamentModel = new TournamentModel();
        $teamModel = new TeamModel();

        $tournament = null;
        if ($tournament_id !== null) {
            $tournament = $tournamentModel->find($tournament_id);
            if (empty($tournament)) {
                throw new \CodeIgniter\Exceptions\PageNotFoundException('Turnamen tidak ditemukan');
            }
        }

        $name = $this->request->getPost('name');
        $tag = $this->request->getPost('tag');

        $data = [
            'tournament_id' => $tournament_id,
            'name' => $name,
            'tag' => $tag,
        ];

        // Simpan tim secara lokal terlebih dahulu untuk mendapatkan ID
        $team_id = $teamModel->insert($data, true); // true untuk mengembalikan ID yang di-insert

        if ($team_id) {
            // Integrasi dengan Challonge API: Tambahkan peserta ke Challonge jika ada challonge_id
            if (!empty($tournament['challonge_id'])) {
                $challonge_participant = $this->addChallongeParticipant($tournament['challonge_id'], $name);
                if (isset($challonge_participant['participant']['id'])) {
                    // Update data tim lokal dengan challonge_participant_id
                    $teamModel->update($team_id, ['challonge_participant_id' => $challonge_participant['participant']['id']]);
                    return redirect()->to('/tournaments/teams/' . $tournament_id)->with('success', 'Tim berhasil ditambahkan dan disinkronkan dengan Challonge.');
                } else {
                    log_message('error', 'Gagal menambahkan peserta ke Challonge: ' . json_encode($challonge_participant));
                    return redirect()->to('/tournaments/teams/' . $tournament_id)->with('warning', 'Tim berhasil ditambahkan secara lokal, tetapi gagal disinkronkan dengan Challonge.');
                }
            }
            return redirect()->to('/tournaments/teams/' . $tournament_id)->with('success', 'Tim berhasil ditambahkan.');
        } else {
            return redirect()->to('/tournaments/teams/' . $tournament_id)->with('error', 'Gagal menambahkan tim.');
        }
    }

    /**
     * Menghapus tim dari database lokal dan Challonge.
     */
    public function deleteTeam($tournament_id, $team_id)
    {
        $tournamentModel = new TournamentModel();
        $teamModel = new TeamModel();

        $tournament = $tournamentModel->find($tournament_id);
        $team = $teamModel->find($team_id);

        if (empty($tournament) || empty($team)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Turnamen atau Tim tidak ditemukan');
        }

        // Hapus dari Challonge jika ada challonge_id dan challonge_participant_id
        if (!empty($tournament['challonge_id']) && !empty($team['challonge_participant_id'])) {
            $challonge_response = $this->deleteChallongeParticipant($tournament['challonge_id'], $team['challonge_participant_id']);
            if (isset($challonge_response['error'])) {
                log_message('error', 'Gagal menghapus peserta dari Challonge: ' . json_encode($challonge_response));
                // Anda bisa memilih untuk tetap menghapus lokal atau tidak
                $teamModel->delete($team_id);
                return redirect()->to('/tournaments/teams/' . $tournament_id)->with('warning', 'Tim berhasil dihapus secara lokal, tetapi gagal dihapus dari Challonge.');
            }
        }

        $teamModel->delete($team_id);
        return redirect()->to('/tournaments/teams/' . $tournament_id)->with('success', 'Tim berhasil dihapus.');
    }

    public function importTeams($tournament_id)
    {
        $tournamentModel = new TournamentModel();
        $tournament = $tournamentModel->find($tournament_id);

        if (empty($tournament)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Turnamen tidak ditemukan');
        }

        $file = $this->request->getFile('excel_file');
        if ($file->isValid() && !$file->hasMoved()) {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getTempName());
            $sheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

            $teamModel = new TeamModel();
            $imported_count = 0;
            $challonge_sync_failed_count = 0;

            foreach ($sheet as $row) {
                if ($row['A'] != 'Nama Tim' && !empty($row['A'])) { // Lewati header dan baris kosong
                    $team_name = $row['A'];
                    $team_tag = $row['B'] ?? null;

                    $data = [
                        'tournament_id' => $tournament_id,
                        'name' => $team_name,
                        'tag' => $team_tag,
                    ];

                    // Simpan tim secara lokal
                    $team_id = $teamModel->insert($data, true);

                    if ($team_id) {
                        $imported_count++;
                        // Tambahkan ke Challonge jika ada challonge_id
                        if (!empty($tournament['challonge_id'])) {
                            $challonge_participant = $this->addChallongeParticipant($tournament['challonge_id'], $team_name);
                            if (isset($challonge_participant['participant']['id'])) {
                                $teamModel->update($team_id, ['challonge_participant_id' => $challonge_participant['participant']['id']]);
                            } else {
                                $challonge_sync_failed_count++;
                                log_message('error', 'Gagal menambahkan peserta "' . $team_name . '" ke Challonge: ' . json_encode($challonge_participant));
                            }
                        }
                    }
                }
            }
            $message = "$imported_count tim berhasil diimpor.";
            if ($challonge_sync_failed_count > 0) {
                $message .= " ($challonge_sync_failed_count tim gagal disinkronkan dengan Challonge).";
                return redirect()->to('/tournaments/teams/' . $tournament_id)->with('warning', $message);
            }
            return redirect()->to('/tournaments/teams/' . $tournament_id)->with('success', $message);
        }
        return redirect()->to('/tournaments/teams/' . $tournament_id)->with('error', 'Gagal mengunggah file Excel atau file tidak valid.');
    }

    public function exportTeamsExcel($tournament_id)
    {
        $teamModel = new TeamModel();
        $teams = $teamModel->where('tournament_id', $tournament_id)->findAll();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Nama Tim');
        $sheet->setCellValue('B1', 'Tag');
        $sheet->setCellValue('C1', 'Challonge Participant ID'); // Tambahkan kolom ini

        $row = 2;
        foreach ($teams as $team) {
            $sheet->setCellValue('A' . $row, $team['name']);
            $sheet->setCellValue('B' . $row, $team['tag']);
            $sheet->setCellValue('C' . $row, $team['challonge_participant_id'] ?? ''); // Tampilkan ID Challonge
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'daftar_tim.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
    }

    public function exportTeamsPdf($tournament_id)
    {
        $teamModel = new TeamModel();
        $data['teams'] = $teamModel->where('tournament_id', $tournament_id)->findAll();
        $tournamentModel = new TournamentModel();
        $data['tournament'] = $tournamentModel->find($tournament_id);

        $html = view('tournaments/pdf_template', $data);

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('daftar_tim.pdf');
    }

    // =================================================================
    // FUNGSI OAUTH2 CHALLONGE (untuk otorisasi pengguna, jika diperlukan)
    // =================================================================

    /**
     * Generate the authorization URL for Challonge OAuth2
     */
    public function getAuthorizeUrl()
    {
        $params = [
            'client_id' => self::CLIENT_ID,
            'redirect_uri' => self::REDIRECT_URI,
            'response_type' => 'code',
            'scope' => 'me tournaments:read tournaments:write matches:read matches:write participants:read participants:write',
        ];
        return self::OAUTH_ROOT_URL . '/oauth/authorize?' . http_build_query($params);
    }

    /**
     * Redirect user to Challonge OAuth2 authorization page
     */
    public function authorize()
    {
        return redirect()->to($this->getAuthorizeUrl());
    }

    /**
     * Handle OAuth2 callback with authorization code
     */
    public function oauthCallback()
    {
        $code = $this->request->getGet('code');
        if (!$code) {
            return redirect()->back()->with('error', 'Authorization code not found');
        }

        // Exchange authorization code for access token
        $response = $this->http->post(self::OAUTH_ROOT_URL . '/oauth/token', [
            'form_params' => [
                'client_id' => self::CLIENT_ID,
                'client_secret' => self::CLIENT_SECRET,
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => self::REDIRECT_URI,
            ],
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
        ]);

        $data = json_decode($response->getBody(), true);

        if (isset($data['access_token'])) {
            // Store tokens in session or database as needed
            $this->session->set('challonge_access_token', $data['access_token']);
            $this->session->set('challonge_refresh_token', $data['refresh_token']);
            return redirect()->to('/tournaments')->with('message', 'Challonge OAuth successful');
        } else {
            return redirect()->back()->with('error', 'Failed to get access token');
        }
    }

    /**
     * Refresh Challonge access token using refresh token
     */
    public function refreshToken()
    {
        $refresh_token = $this->session->get('challonge_refresh_token');
        if (!$refresh_token) {
            return redirect()->back()->with('error', 'No refresh token found');
        }

        $response = $this->http->post(self::OAUTH_ROOT_URL . '/oauth/token', [
            'form_params' => [
                'client_id' => self::CLIENT_ID,
                'client_secret' => self::CLIENT_SECRET,
                'grant_type' => 'refresh_token',
                'refresh_token' => $refresh_token,
                'redirect_uri' => self::REDIRECT_URI,
            ],
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
        ]);

        $data = json_decode($response->getBody(), true);

        if (isset($data['access_token'])) {
            $this->session->set('challonge_access_token', $data['access_token']);
            if (isset($data['refresh_token'])) {
                $this->session->set('challonge_refresh_token', $data['refresh_token']);
            }
            return redirect()->to('/tournaments')->with('message', 'Challonge token refreshed');
        } else {
            return redirect()->back()->with('error', 'Failed to refresh access token');
        }
    }

    /**
     * Menampilkan bracket Challonge untuk turnamen tertentu.
     */
    public function viewBracket($tournament_id)
    {
        $tournamentModel = new TournamentModel();
        $tournament = $tournamentModel->find($tournament_id);

        if (empty($tournament) || empty($tournament['challonge_url'])) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Turnamen tidak ditemukan atau URL Challonge tidak tersedia');
        }

        $challongeUrl = $tournament['challonge_url'];

        // Embed Challonge bracket in local view
        $data['challonge_url'] = $challongeUrl;
        $data['tournament'] = $tournament;

        return view('tournaments/bracket_embed', $data);
    }
}
