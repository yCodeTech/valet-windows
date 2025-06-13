<?php

// DEPRECATED: All legacy drivers are deprecated as of v3.3.0, and will be removed in 4.0.0.

// All legacy drivers extend their new namespaced drivers to make sure user custom drivers are
// backwards compatible.

use Valet\Drivers\ValetDriver as RealValetDriver;

abstract class ValetDriver extends RealValetDriver {
}
