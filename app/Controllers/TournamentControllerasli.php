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
    private const REDIRECT_URI = "https://auth.advancedrestclient.com/oauth-popup.html"; // for debugging, can be changed
    private const VERIFY_SSL = true;

    private const OAUTH_ROOT_URL = "https://api." . self::DOMAIN;
    private const API_ROOT_URL = "https://api." . self::DOMAIN . "/v2";

    public function __construct()
    {
        helper('url');
        $this->session = \Config\Services::session();
        $this->http = \Config\Services::curlrequest([
            'verify' => self::VERIFY_SSL,
        ]);
    }
    private function getAccessToken()
    {
        $response = $this->http->post(self::OAUTH_ROOT_URL . "/oauth/token", [
            'form_params' => [
                'client_id' => self::CLIENT_ID,
                'client_secret' => self::CLIENT_SECRET,
                'grant_type' => 'client_credentials',
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['access_token'] ?? null;
    }
    private function requestChallonge($method, $uri, $data = [])
    {
        $accessToken = $this->getAccessToken();

        $options = [
            'headers' => [
                'Authorization' => "Bearer $accessToken",
                'Content-Type' => 'application/json',
            ],
            'json' => $data
        ];

        $response = $this->http->request($method, self::API_ROOT_URL . $uri, $options);
        return json_decode($response->getBody(), true);
    }


    public function index()
    {
        $api = 
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
     * Menyimpan turnamen baru ke database.
     */
    public function store()
    {
        $tournamentModel = new TournamentModel();
        $data = [
            'name' => $this->request->getPost('name'),
            'game_name' => $this->request->getPost('game_name'),
            'description' => $this->request->getPost('description'),
            'tournament_type' => $this->request->getPost('tournament_type'),
            'start_at' => $this->request->getPost('start_at'),
        ];

        // Opsional: Integrasi dengan Challonge API
        // $challonge_data = $this->createChallongeTournament($data['name'], $data['tournament_type']);
        // if (isset($challonge_data->data->id)) {
        //     $data['challonge_id'] = $challonge_data->data->id;
        // }

        $tournamentModel->insert($data);
        return redirect()->to('/tournaments');
    }

    /**
     * Menampilkan form untuk mengedit turnamen.
     */
    public function edit($id)
    {
        $tournamentModel = new TournamentModel();
        $data['tournament'] = $tournamentModel->find($id);
        return view('tournaments/edit', $data);
    }

    /**
     * Mengupdate data turnamen di database.
     */
    public function update($id)
    {
        $tournamentModel = new TournamentModel();
        $data = [
            'name' => $this->request->getPost('name'),
            'game_name' => $this->request->getPost('game_name'),
            'description' => $this->request->getPost('description'),
            'tournament_type' => $this->request->getPost('tournament_type'),
            'start_at' => $this->request->getPost('start_at'),
        ];
        $tournamentModel->update($id, $data);
        return redirect()->to('/tournaments');
    }

    /**
     * Menghapus turnamen dari database.
     */
    public function delete($id)
    {
        $tournamentModel = new TournamentModel();
        $tournamentModel->delete($id);
        return redirect()->to('/tournaments');
    }

    // =================================================================
    // FUNGSI CRUD UNTUK TIM
    // =================================================================

    /**
     * Menampilkan semua tim dalam sebuah turnamen.
     */
    public function teams($tournament_id)
    {
        $tournamentModel = new TournamentModel();
        $teamModel = new TeamModel();
        
        $data['tournament'] = $tournamentModel->find($tournament_id);
        $data['teams'] = $teamModel->where('tournament_id', $tournament_id)->findAll();

        if (empty($data['tournament'])) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Turnamen tidak ditemukan');
        }

        return view('tournaments/teams', $data);
    }

    /**
     * Menyimpan tim baru ke database.
     */
    public function storeTeam($tournament_id)
    {
        $teamModel = new TeamModel();
        $data = [
            'tournament_id' => $tournament_id,
            'name' => $this->request->getPost('name'),
            'tag' => $this->request->getPost('tag'),
        ];
        $teamModel->insert($data);
        return redirect()->to('/tournaments/teams/' . $tournament_id);
    }

    /**
     * Menghapus tim dari database.
     */
    public function deleteTeam($tournament_id, $team_id)
    {
        $teamModel = new TeamModel();
        $teamModel->delete($team_id);
        return redirect()->to('/tournaments/teams/' . $tournament_id);
    }

    public function importTeams($tournament_id)
    {
        $file = $this->request->getFile('excel_file');
        if ($file->isValid() && !$file->hasMoved()) {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getTempName());
            $sheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

            $teamModel = new TeamModel();
            foreach ($sheet as $row) {
                if ($row['A'] != 'Nama Tim') { // Lewati header
                    $data = [
                        'tournament_id' => $tournament_id,
                        'name' => $row['A'],
                        'tag' => $row['B'] ?? null,
                    ];
                    $teamModel->insert($data);
                }
            }
        }
        return redirect()->to('/tournaments/teams/' . $tournament_id);
    }

    public function exportTeamsExcel($tournament_id)
    {
        $teamModel = new TeamModel();
        $teams = $teamModel->where('tournament_id', $tournament_id)->findAll();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Nama Tim');
        $sheet->setCellValue('B1', 'Tag');

        $row = 2;
        foreach ($teams as $team) {
            $sheet->setCellValue('A' . $row, $team['name']);
            $sheet->setCellValue('B' . $row, $team['tag']);
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
    
}
