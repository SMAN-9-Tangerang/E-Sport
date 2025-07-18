<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
// Rute untuk Turnamen
$routes->group('tournaments', ['namespace' => 'App\Controllers'], function($routes) {
    $routes->get('/', 'TournamentController::index');
    $routes->get('create', 'TournamentController::create', ['filter' => 'admin']);
    $routes->post('store', 'TournamentController::store', ['filter' => 'admin']);
    $routes->get('edit/(:num)', 'TournamentController::edit/$1', ['filter' => 'admin']);
    $routes->put('update/(:num)', 'TournamentController::update/$1', ['filter' => 'admin']);
    $routes->get('oauth-callback', 'TournamentController::oauthCallback');
    $routes->delete('delete/(:num)', 'TournamentController::delete/$1', ['filter' => 'admin']);
    $routes->get('teams/(:num)', 'TournamentController::teams/$1', ['filter' => 'user']);
    $routes->get('view-bracket/(:num)', 'TournamentController::viewBracket/$1', ['filter' => 'user']);
    $routes->post('teams/store/(:num)', 'TournamentController::storeTeam/$1', ['filter' => 'user']);
    $routes->delete('teams/delete/(:num)/(:num)', 'TournamentController::deleteTeam/$1/$2', ['filter' => 'user']);
    $routes->get('export-excel/(:num)', 'TournamentController::exportTeamsExcel/$1');
    $routes->get('export-pdf/(:num)', 'TournamentController::exportTeamsPdf/$1');
    $routes->get('manage-team/(:num)', 'TournamentController::manageTeam/$1', ['filter' => 'user']);
    
    // Custom API-based routes (Challonge)
    $routes->post('(:num)/start', 'TournamentController::startTournament/$1');
    $routes->post('(:num)/participants/add', 'TournamentController::addParticipant/$1');
    $routes->get('(:num)/matches', 'TournamentController::getMatches/$1');
    $routes->put('(:num)/matches/(:num)/update', 'TournamentController::updateMatch/$1/$2');
    
    // Rute untuk Challonge OAuth2
});
$routes->get('/tournaments/authorize', 'TournamentController::authorize');
$routes->get('/tournaments/oauth-callback', 'TournamentController::oauthCallback');
$routes->get('/tournaments/refresh-token', 'TournamentController::refreshToken');

$routes->get('tournaments/admin-login', 'TournamentController::adminLogin');
$routes->post('tournaments/admin-login-post', 'TournamentController::adminLoginPost');
$routes->get('tournaments/user-login', 'TournamentController::userLogin');
$routes->post('tournaments/user-login-post', 'TournamentController::userLoginPost');

$routes->get('auth/logout', 'TournamentController::logout');

$routes->get('tournaments/user-dashboard', 'TournamentController::userDashboard', ['filter' => 'user']);

$routes->get('tournaments/create-user-team', 'TournamentController::createUserTeam', ['filter' => 'user']);
$routes->post('tournaments/store-user-team', 'TournamentController::storeUserTeam', ['filter' => 'user']);
