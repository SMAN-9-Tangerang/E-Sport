<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// RESTful resource untuk TournamentController
$routes->group('tournaments', ['namespace' => 'App\Controllers'], static function ($routes) {
    // API routes
    $routes->get('/', 'TournamentController::index'); // GET /tournaments (API)
    $routes->get('(:num)', 'TournamentController::show/$1'); // GET /tournaments/1 (API)
    $routes->post('/', 'TournamentController::create'); // POST /tournaments (API)
    $routes->put('(:num)', 'TournamentController::update/$1'); // PUT /tournaments/1 (API)
    $routes->patch('(:num)', 'TournamentController::update/$1'); // PATCH /tournaments/1 (API)
    $routes->delete('(:num)', 'TournamentController::delete/$1'); // DELETE /tournaments/1 (API)

    // View routes
    $routes->get('list', 'TournamentController::listView'); // GET /tournaments/list (View)
    $routes->get('create', 'TournamentController::createView'); // GET /tournaments/create (View)
    $routes->get('edit/(:num)', 'TournamentController::editView/$1'); // GET /tournaments/edit/1 (View)
    $routes->get('edit-sync/(:num)', 'TournamentController::editSyncView/$1'); // GET /tournaments/edit-sync/1 (View)
    $routes->post('edit-sync/(:num)', 'TournamentController::editSync/$1'); // POST /tournaments/edit-sync/1 (Sync update via form)
    $routes->get('delete-sync/(:num)', 'TournamentController::deleteSync/$1'); // GET /tournaments/delete-sync/1 (Sync delete via form)

    // Admin login (API & View)
    $routes->post('login-admin', 'TournamentController::loginAdmin'); // POST /tournaments/login-admin (API)
    $routes->get('login-admin', 'TournamentController::loginAdminView'); // GET /tournaments/login-admin (View)

    // User login (API & View)
    $routes->post('login-user', 'TournamentController::loginUser'); // POST /tournaments/login-user (API)
    $routes->get('login-user', 'TournamentController::loginUserView'); // GET /tournaments/login-user (View)
});
