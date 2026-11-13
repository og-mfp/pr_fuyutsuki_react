<?php

test('home redirects to setup before installation', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect(route('setup.create'));
});
