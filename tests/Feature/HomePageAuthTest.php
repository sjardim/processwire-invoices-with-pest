<?php

beforeEach(function() {
    // Get the admin user (ID 41 is the default admin user created during install)
    $admin = pages()->get("id=41");
    if (!$admin->id) {
        // Fallback to finding admin by name/role if ID is different
        $admin = pages()->get("template=user, name=admin");
    }
    
    // Store the current user so we can restore it later
    $this->previousUser = wire('user');
    
    // Log in as admin
    wire('users')->setCurrentUser($admin);
});

afterEach(function() {
    // Restore the previous user
    if (isset($this->previousUser)) {
        wire('users')->setCurrentUser($this->previousUser);
    }
});

it('loads homepage for authenticated admin', function() {
    $homepage = pages()->get('/');
    
    expect($homepage)
        ->toBeInstanceOf(\ProcessWire\Page::class)
        ->and($homepage->id)->toBe(1)
        ->and($homepage->template->name)->toBe('home')
        ->and($homepage->title)->toBe('Home');
});

it('verifies admin has necessary permissions', function() {
    $user = wire('user');
    
    expect($user)
        ->toBeInstanceOf(\ProcessWire\User::class)
        ->and($user->isSuperuser())->toBeTrue()
        ->and($user->hasPermission('page-edit'))->toBeTrue()
        ->and($user->hasPermission('page-delete'))->toBeTrue()
        ->and($user->hasPermission('page-create'))->toBeTrue();
});

it('allows admin to access homepage fields', function() {
    $homepage = pages()->get('/');
    $user = wire('user');
    
    expect($user->hasPermission('page-view', $homepage))->toBeTrue();
    
    // Test access to specific fields - adjust these based on your homepage template
    $fields = $homepage->template->fields;
    foreach ($fields as $field) {
        expect($user->hasPermission('page-edit', $homepage))
            ->toBeTrue("Admin should have edit permission for field {$field->name}");
    }
});

it('has expected admin navigation access', function() {
    $admin = wire('user');
    
    expect($admin)
        ->hasPermission('page-view')->toBeTrue()
        ->hasPermission('page-edit-recent')->toBeTrue()
        ->hasPermission('user-admin')->toBeTrue();
        
    // Test if admin can access important admin pages
    $adminPage = pages()->get('/admin/');
    $setupPage = pages()->get('/admin/setup/');
    $modulesPage = pages()->get('/admin/modules/');
    
    expect($admin->hasPermission('page-view', $adminPage))->toBeTrue();
    expect($admin->hasPermission('page-view', $setupPage))->toBeTrue();
    expect($admin->hasPermission('page-view', $modulesPage))->toBeTrue();
});

it('can modify homepage as admin', function() {
    $homepage = pages()->get('/');
    $originalTitle = $homepage->title;
    
    // Test modifying the homepage
    $newTitle = 'Test Homepage Title ' . time();
    $homepage->of(false); // Turn off output formatting
    $homepage->title = $newTitle;
    $homepage->save();
    
    // Verify the change
    $modifiedPage = pages()->get(1); // Get fresh instance
    expect($modifiedPage->title)->toBe($newTitle);
    
    // Restore original title
    $homepage->title = $originalTitle;
    $homepage->save();
});

it('verifies admin session is working', function() {
    $session = wire('session');
    
    expect($session->CSRF)
        ->toBeObject()
        ->not->toBeEmpty();
        
    expect(wire('user')->isLoggedin())->toBeTrue();
    expect(wire('user')->hasRole('superuser'))->toBeTrue();
});

it('maintains admin permissions across requests', function() {
    $admin = wire('user');
    $homepage = pages()->get('/');
    
    // Test a sequence of operations that should maintain permissions
    expect($admin->isSuperuser())->toBeTrue();
    
    // Add a test child page
    $testPage = pages()->add('basic-page', $homepage, [
        'title' => 'Test Page ' . time(),
        'name' => 'test-page-' . time()
    ]);
    
    expect($testPage->id)->toBeGreaterThan(0);
    expect($admin->hasPermission('page-edit', $testPage))->toBeTrue();
    
    // Clean up
    pages()->delete($testPage);
});