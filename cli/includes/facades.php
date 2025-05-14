<?php

use Illuminate\Container\Container;

class Facade {
	/**
	 * The key for the binding in the container.
	 *
	 * @return string
	 */
	public static function containerKey() {
		// Default namespace.
		$namespace = "Valet\\";

		// If the class is any of the strings in the array, then append the ShareTools string
		// to the default Valet namespace, to use the ShareTools namespace.
		if (in_array(get_called_class(), ["Ngrok"])) {
			$namespace .= "ShareTools\\";
		}
		// If the class is any of the strings in the array, then append the Packages string
		// to the default Valet namespace, to use the Packages namespace.
		elseif (in_array(get_called_class(), ["Ansicon", "Gsudo"])) {
			$namespace .= "Packages\\";
		}

		return $namespace . basename(str_replace('\\', '/', get_called_class()));
	}

	/**
	 * Call a non-static method on the facade.
	 *
	 * @param string $method
	 * @param array $parameters
	 * @return mixed
	 */
	public static function __callStatic($method, $parameters) {
		$resolvedInstance = Container::getInstance()->make(static::containerKey());

		return call_user_func_array([$resolvedInstance, $method], $parameters);
	}
}

class Acrylic extends Facade {}
class Ansicon extends Facade {}
class CommandLine extends Facade {}
class Configuration extends Facade {}
class Diagnose extends Facade {}
class Filesystem extends Facade {}
class Gsudo extends Facade {}
class Nginx extends Facade {}
class Ngrok extends Facade {}
class PhpCgi extends Facade {}
class PhpCgiXdebug extends Facade {}
class Share extends Facade {}
class Site extends Facade {}
class Upgrader extends Facade {}
class Valet extends Facade {}
class ValetException extends Facade {}
class WinSW extends Facade {}