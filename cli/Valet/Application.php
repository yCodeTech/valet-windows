<?php

namespace Valet;

use Silly\Application as SillyApplication;

class Application extends SillyApplication {
	/**
	 * @var string
	 */
	private static $logo =
	"     _                               _  __      __   _      _       ____________
    | |                             | | \ \    / /  | |    | |     |            \
    | |     __ _ _ __ __ ___   _____| |  \ \  / /_ _| | ___| |_    |________     |
    | |    / _` | '__/ _` \ \ / / _ \ |   \ \/ / _` | |/ _ \ __|            |    |
    | |___| (_| | | | (_| |\ V /  __/ |    \  / (_| | |  __/ |_       ______|    /
    |______\__,_|_|  \__,_| \_/ \___|_|     \/ \__,_|_|\___|\__|     |          /
                     _       ___           __                        |______    \
                    | |     / (_)___  ____/ /___ _      _______             |    \
                    | | /| / / / __ \/ __  / __ \ | /| / / ___/     ________|    |
                    | |/ |/ / / / / / /_/ / /_/ / |/ |/ (__  )     |             |
                    |__/|__/_/_/ /_/\__,_/\____/|__/|__/____/      |____________/

    ";
	public function getHelp(): string {
		return self::$logo . parent::getHelp();
	}
}