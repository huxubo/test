<?php
declare(strict_types=1);

route()->get('/', function () {
    if (\Core\Auth::check()) {
        redirect('/dashboard');
    }

    return view('home');
});

route()->get('/login', 'AuthController@showLogin');
route()->post('/login', 'AuthController@login');
route()->get('/register', 'AuthController@showRegister');
route()->post('/register', 'AuthController@register');
route()->get('/verify', 'AuthController@verify');
route()->get('/logout', 'AuthController@logout');

route()->get('/dashboard', 'DashboardController@index');
route()->post('/subdomains', 'DashboardController@storeSubdomain');
route()->get('/subdomains/check', 'DashboardController@checkAvailability');
route()->post('/subdomains/transfer', 'DashboardController@transfer');
route()->post('/subdomains/update', 'DashboardController@updateSubdomain');
route()->post('/subdomains/renew', 'DashboardController@renewSubdomain');
route()->post('/subdomains/delete', 'DashboardController@deleteSubdomain');

route()->get('/admin', 'AdminController@dashboard');
route()->get('/admin/settings', 'AdminController@settings');
route()->post('/admin/settings', 'AdminController@updateSettings');
route()->get('/admin/providers', 'AdminController@providers');
route()->post('/admin/providers/save', 'AdminController@saveProvider');
route()->get('/admin/domains', 'AdminController@domains');
route()->post('/admin/domains/save', 'AdminController@saveDomain');
route()->get('/admin/subdomains', 'AdminController@subdomains');
route()->post('/admin/subdomains/approve', 'AdminController@approveSubdomain');
route()->post('/admin/subdomains/reject', 'AdminController@rejectSubdomain');
