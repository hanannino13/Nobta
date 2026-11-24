<?php

it('runs the unit suite without booting the framework', function () {
    expect(PHP_VERSION_ID)->toBeGreaterThanOrEqual(80300);
});
