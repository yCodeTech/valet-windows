<?php

namespace Valet;

use Exception;

class ValetException extends Exception {
	/**
	 * Construct and return the error message.
	 *
	 * @return string
	 */
	public function getError() {
		$errorMsg = $this->getMessage();
		$errorTypeName = $this->getErrorTypeName($this->getCode());
		$constructTrace = $this->constructTrace();

		return "$errorTypeName: $errorMsg\n\n$constructTrace";
	}

	/**
	 * Get the error type name.
	 * Eg.: Inputs error code `0`, outputs error name `"FATAL"`
	 *
	 * @param mixed $code The numeric error type/code
	 * @return string The error type name
	 */
	private function getErrorTypeName($code) {
		return $code == 0 ? "FATAL" : array_search($code, get_defined_constants(true)['Core']);
	}

	/**
	 * Construct a better-formatted error trace.
	 *
	 * @return string
	 */
	private function constructTrace() {
		$constructTrace = [];
		$count = 0;
		foreach ($this->getTrace() as $key => $value) {
			$count_num = $count++ . ") ";
			$class = $value["class"] ?? "";
			$type = $value["type"] ?? "";
			$func = $value["function"] ?? "";

			$file_n_line = isset($value["file"]) ?
			" ------ " . $value["file"] . ":" . $value["line"] : "";

			$constructTrace[] = $count_num . $class . $type . $func . $file_n_line;
		}
		return implode("\n", $constructTrace);
	}
}