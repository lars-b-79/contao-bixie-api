<?php

declare(strict_types=1);

/*
 * This file is part of [package name].
 *
 * (c) John Doe
 *
 * @license LGPL-3.0-or-later
 */

namespace pcak\BixieApi\Tests;

use pcak\BixieApi\BixieApiBundle;
use PHPUnit\Framework\TestCase;

class BixieApiBundleTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $bundle = new BixieApiBundle();

        $this->assertInstanceOf('pcak\BixieApi\BixieApiBundle', $bundle);
    }
}
