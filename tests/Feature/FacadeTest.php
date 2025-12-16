<?php

declare(strict_types=1);

use OffloadProject\Navigation\Facades\Navigation;

it('can use facade to get navigation', function () {
    config(['navigation' => $this->getTestConfig()]);

    $tree = Navigation::get('main')->toTree();

    expect($tree)->toBeArray()
        ->and($tree)->not->toBeEmpty();
});

it('can use facade to get breadcrumbs', function () {
    config(['navigation' => $this->getTestConfig()]);

    $breadcrumbs = Navigation::breadcrumbs('main', 'users.index');

    expect($breadcrumbs)->toBeArray();
});
