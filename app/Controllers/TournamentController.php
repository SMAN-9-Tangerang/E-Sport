<?php
namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;

class TournamentController extends ResourceController
{
    use ResponseTrait;

    protected $modelName = 'App\Models\TournamentModel';
    protected $format    = 'json';

    // Challonge API credentials
    private $challongeApiKey = '3x4njJpepxe1jDB63YOEWWA4xu68Fsnd13JnObjl';
    private $challongeUsername = 'smanlanta';
    private $challongeBaseUrl = 'https://api.challonge.com/v1';

    // List all tournaments (local + Challonge)
    public function __construct()
    {
        helper('session');
        $this->session = session();
    }
    public function index()
    {
        $localTournaments = $this->model->findAll();

        $challongeTournaments = $this->getChallongeTournaments();

        return $this->respond([
            'local' => $localTournaments,
            'challonge' => $challongeTournaments
        ]);
    }
    // Show tournament list view
    // Show merged tournament list (local + Challonge)
    public function listView()
    {
        // Get local tournaments
        $localTournaments = $this->model->findAll();

        // Get Challonge tournaments
        $challongeTournaments = $this->getChallongeTournaments();

        // Flatten Challonge tournaments array
        $challongeList = [];
        if (is_array($challongeTournaments)) {
            foreach ($challongeTournaments as $item) {
                if (isset($item['tournament'])) {
                    $challongeList[] = $item['tournament'];
                }
            }
        }

        // Merge local and Challonge tournaments by challonge_id/id
        $merged = [];
        foreach ($localTournaments as $local) {
            $found = null;
            foreach ($challongeList as $challonge) {
                if ((string)$challonge['id'] === (string)$local['challonge_id']) {
                    $found = $challonge;
                    break;
                }
            }
            $merged[] = [
                'local' => $local,
                'challonge' => $found
            ];
        }

        // Optionally, add Challonge tournaments not in local
        foreach ($challongeList as $challonge) {
            $exists = false;
            foreach ($localTournaments as $local) {
                if ((string)$challonge['id'] === (string)$local['challonge_id']) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $merged[] = [
                    'local' => null,
                    'challonge' => $challonge
                ];
            }
        }

        return view('tournaments/index', ['tournaments' => $merged]);
    }

    // Show create tournament form
    public function createView()
    {
        return view('tournaments/create');
    }
    // Show edit tournament form (local + Challonge)
    public function editSyncView($id = null)
    {
        $tournament = $this->model->find($id);
        if (!$tournament) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Tournament not found');
        }
        $challongeTournament = $this->getChallongeTournament($tournament['challonge_id']);
        return view('tournaments/edit_sync', [
            'tournament' => $tournament,
            'challonge' => $challongeTournament['tournament'] ?? null
        ]);
    }

    // Update tournament (local + Challonge) via form
    public function editSync($id = null)
    {
        $data = $this->request->getPost();

        $tournament = $this->model->find($id);
        if (!$tournament) {
            return $this->failNotFound('Tournament not found');
        }

        // Prepare Challonge update data
        $challongeData = [
            'tournament' => [
                'name' => $data['name'] ?? $tournament['name'],
                'url' => $data['url'] ?? $tournament['url'],
                'tournament_type' => $data['tournament_type'] ?? $tournament['tournament_type'],
                'description' => $data['description'] ?? $tournament['description'],
                'start_at' => $data['start_at'] ?? $tournament['start_at'],
            ]
        ];
        $challongeResponse = $this->putChallongeTournament($tournament['challonge_id'], $challongeData);

        if (isset($challongeResponse['errors'])) {
            return redirect()->back()->with('error', implode(', ', $challongeResponse['errors']));
        }

        // Update local DB
        $updateData = [
            'name' => $data['name'] ?? $tournament['name'],
            'url' => $data['url'] ?? $tournament['url'],
            'description' => $data['description'] ?? $tournament['description'],
            'game_name' => $data['game_name'] ?? $tournament['game_name'],
            'tournament_type' => $data['tournament_type'] ?? $tournament['tournament_type'],
            'start_at' => $data['start_at'] ?? $tournament['start_at'],
        ];
        $this->model->update($id, $updateData);

        return redirect()->to('/tournaments/list')->with('success', 'Tournament updated and synced with Challonge.');
    }

    // Delete tournament (local + Challonge) via form
    public function deleteSync($id = null)
    {
        $tournament = $this->model->find($id);
        if (!$tournament) {
            return redirect()->back()->with('error', 'Tournament not found');
        }

        // Delete from Challonge
        $this->deleteChallongeTournament($tournament['challonge_id']);

        // Delete from local DB
        $this->model->delete($id);

        return redirect()->to('/tournaments/list')->with('success', 'Tournament deleted from local and Challonge.');
    }
    // Show edit tournament form
    public function editView($id = null)
    {
        $tournament = $this->model->find($id);
        if (!$tournament) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Tournament not found');
        }
        return view('tournaments/edit', ['tournament' => $tournament]);
    }
    // Show a single tournament (local + Challonge)
    public function show($id = null)
    {
        $tournament = $this->model->find($id);
        if (!$tournament) {
            return $this->failNotFound('Tournament not found');
        }

        $challongeTournament = $this->getChallongeTournament($tournament['challonge_id']);

        return $this->respond([
            'local' => $tournament,
            'challonge' => $challongeTournament
        ]);
    }

    // Create a new tournament (local + Challonge)
    public function create()
    {
        $data = $this->request->getJSON(true);

        // Generate URL automatically if not provided, following Challonge rules
        if (empty($data['url']) && !empty($data['name'])) {
            $baseUrl = strtolower($data['name']);
            $baseUrl = preg_replace('/[^a-z0-9_]+/', '_', $baseUrl); // replace non-alphanumeric/underscore with underscore
            $baseUrl = preg_replace('/_+/', '_', $baseUrl); // no consecutive underscores
            $baseUrl = trim($baseUrl, '_'); // no leading/trailing underscore
            if (!preg_match('/^[a-z]/', $baseUrl)) {
                $baseUrl = 'a' . $baseUrl; // must start with a letter
            }
            $baseUrl = substr($baseUrl, 0, 60); // max 60 chars
            if (strlen($baseUrl) < 6) {
                $baseUrl = str_pad($baseUrl, 6, 'a'); // min 6 chars
            }
            $url = $baseUrl;
            $suffix = 1;
            // Check Challonge for URL availability
            while (true) {
                $challongeCheck = $this->getChallongeTournament($url);
                if (isset($challongeCheck['errors']) && strpos(implode(' ', $challongeCheck['errors']), 'not found') !== false) {
                    // URL is available
                    break;
                }
                $url = $baseUrl . $suffix;
                $suffix++;
            }
            $data['url'] = $url;
        }

        // Create on Challonge
        $challongeData = [
            'tournament' => [
                'name' => $data['name'],
                'url' => $data['url'],
                'tournament_type' => $data['tournament_type'] ?? 'single elimination',
                'description' => $data['description'] ?? '',
                'start_at' => $data['start_at'] ?? null,
                // Challonge tidak punya game_name, ini hanya untuk lokal
            ]
        ];
        $challongeResponse = $this->postChallongeTournament($challongeData);

        if (isset($challongeResponse['errors'])) {
            return $this->failValidationErrors($challongeResponse['errors']);
        }

        // Save to local DB
        $saveData = [
            'name' => $data['name'],
            'url' => $data['url'],
            'description' => $data['description'] ?? '',
            'challonge_id' => $challongeResponse['tournament']['id'],
            // 'challonge_url' => $challongeResponse['tournament']['full_challonge_url'], // Removed because column does not exist
            'game_name' => $data['game_name'] ?? '',
            'tournament_type' => $data['tournament_type'] ?? 'single elimination',
            'start_at' => $data['start_at'] ?? null,
        ];
        $this->model->insert($saveData);

        return $this->respondCreated($saveData);
    }

    // Update a tournament (local + Challonge)
    public function update($id = null)
    {
        $data = $this->request->getJSON(true);

        // Generate URL automatically if not provided but name is updated, following Challonge rules
        if (empty($data['url']) && !empty($data['name'])) {
            $url = strtolower($data['name']);
            $url = preg_replace('/[^a-z0-9]+/', '-', $url); // replace non-alphanumeric with dash
            $url = preg_replace('/-+/', '-', $url); // no consecutive dashes
            $url = trim($url, '-'); // no leading/trailing dash
            if (!preg_match('/^[a-z]/', $url)) {
                $url = 'a' . $url; // must start with a letter
            }
            $url = substr($url, 0, 60); // max 60 chars
            if (strlen($url) < 6) {
                $url = str_pad($url, 6, 'a'); // min 6 chars
            }
            $data['url'] = $url;
        }

        $tournament = $this->model->find($id);
        if (!$tournament) {
            return $this->failNotFound('Tournament not found');
        }

        // Generate URL automatically if not provided but name is updated
        if (empty($data['url']) && !empty($data['name'])) {
            $data['url'] = strtolower(preg_replace('/[^a-z0-9\-]/', '', str_replace(' ', '-', $data['name'])));
        }

        // Update on Challonge
        $challongeData = [
            'tournament' => [
                'name' => $data['name'] ?? $tournament['name'],
                'url' => $data['url'] ?? $tournament['url'],
                'tournament_type' => $data['tournament_type'] ?? $tournament['tournament_type'],
                'description' => $data['description'] ?? $tournament['description'],
                'start_at' => $data['start_at'] ?? $tournament['start_at'],
            ]
        ];
        $challongeResponse = $this->putChallongeTournament($tournament['challonge_id'], $challongeData);

        if (isset($challongeResponse['errors'])) {
            return $this->failValidationErrors($challongeResponse['errors']);
        }

        // Update local DB
        $updateData = [
            'name' => $data['name'] ?? $tournament['name'],
            'url' => $data['url'] ?? $tournament['url'],
            'description' => $data['description'] ?? $tournament['description'],
            'game_name' => $data['game_name'] ?? $tournament['game_name'],
            'tournament_type' => $data['tournament_type'] ?? $tournament['tournament_type'],
            'start_at' => $data['start_at'] ?? $tournament['start_at'],
        ];
        $this->model->update($id, $updateData);

        return $this->respond($updateData);
    }

    // Delete a tournament (local + Challonge)
    public function delete($id = null)
    {
        $tournament = $this->model->find($id);
        if (!$tournament) {
            return $this->failNotFound('Tournament not found');
        }

        // Delete from Challonge
        // Delete from Challonge
        $this->deleteChallongeTournament($tournament['challonge_id']);

        // Delete from local DB
        $this->model->delete($id);
        return $this->respondDeleted(['message' => 'Tournament deleted']);
    }

    // --- Challonge API Integration ---

    private function getChallongeTournaments()
    {
        $url = $this->challongeBaseUrl . '/tournaments.json';
        return $this->challongeRequest('GET', $url);
    }

    private function getChallongeTournament($challongeId)
    {
        $url = $this->challongeBaseUrl . "/tournaments/{$challongeId}.json";
        return $this->challongeRequest('GET', $url);
    }

    private function postChallongeTournament($data)
    {
        $url = $this->challongeBaseUrl . '/tournaments.json';
        return $this->challongeRequest('POST', $url, $data);
    }

    private function putChallongeTournament($challongeId, $data)
    {
        $url = $this->challongeBaseUrl . "/tournaments/{$challongeId}.json";
        return $this->challongeRequest('PUT', $url, $data);
    }

    private function deleteChallongeTournament($challongeId)
    {
        $url = $this->challongeBaseUrl . "/tournaments/{$challongeId}.json";
        return $this->challongeRequest('DELETE', $url);
    }

    private function challongeRequest($method, $url, $data = null)
    {
        $ch = curl_init();

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->challongeUsername}:{$this->challongeApiKey}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['errors' => [$error]];
        }

        return json_decode($response, true);
    }
    // Admin login
    // Admin login API
    public function loginAdmin()
    {
        $session = session();
        $data = $this->request->getJSON(true);
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        $userModel = new \App\Models\UserModel();
        $user = $userModel->verifyUser($username, $password);

        if ($user && $user['role'] === 'admin') {
            $token = bin2hex(random_bytes(16));
            unset($user['password_hash']);
            // Set session
            $session->set([
                'isLoggedIn' => true,
                'user' => $user,
                'role' => 'admin',
                'token' => $token
            ]);
            // Redirect to tournaments list
            return $this->respond([
                'status' => 'success',
                'redirect' => base_url('/tournaments/list'),
                'token' => $token,
                'role' => 'admin',
                'user' => $user
            ]);
        }

        return $this->failUnauthorized('Invalid admin credentials');
    }

    // Admin login view
    public function loginAdminView()
    {
        $session = session();
        if ($session->get('isLoggedIn')) {
            return redirect()->to('/tournaments/list');
        }
        return view('auth/admin_login');
    }

    // User login
    public function loginUser()
    {
        $session = session();
        $data = $this->request->getJSON(true);
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        $userModel = new \App\Models\UserModel();
        $user = $userModel->verifyUser($username, $password);

        if ($user && $user['role'] === 'user') {
            $token = bin2hex(random_bytes(16));
            unset($user['password_hash']);
            // Set session
            $session->set([
                'isLoggedIn' => true,
                'user' => $user,
                'role' => 'user',
                'token' => $token
            ]);
            // Redirect to tournaments list
            return $this->respond([
                'status' => 'success',
                'redirect' => base_url('/tournaments/list'),
                'token' => $token,
                'role' => 'user',
                'user' => $user
            ]);
        }

        return $this->failUnauthorized('Invalid user credentials');
    }

    // User login view
    public function loginUserView()
    {
        $session = session();
        if ($session->get('isLoggedIn')) {
            return redirect()->to('/tournaments/list');
        }
        return view('auth/user_login');
    }
}