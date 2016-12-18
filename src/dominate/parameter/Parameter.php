<?php
/*
 *   Dominate: Advanced command library for PocketMine-MP
 *   Copyright (C) 2016  Chris Prime
 *
 *   This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace dominate\parameter;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use localizer\Translatable;

class Parameter {

	const TYPE_STRING 	= 0x0;
	const TYPE_INTEGER 	= 0x1;
	const TYPE_NUMERIC 	= self::TYPE_INTEGER;
	const TYPE_FLOAT 	= 0x2;
	const TYPE_DOUBLE 	= self::TYPE_FLOAT;
	const TYPE_REAL 	= self::TYPE_FLOAT;
	const TYPE_BOOLEAN 	= 0x4;
	const TYPE_BOOL 	= self::TYPE_BOOLEAN;
	const TYPE_NULL		= 0x5;

	/** @var string[] */
	public static $ERROR_MESSAGES = [
		self::TYPE_STRING 	=> "parameter.type-string-error",
		self::TYPE_INTEGER 	=> "parameter.type-integer-error",
		self::TYPE_FLOAT 	=> "parameter.type-float-error",
		self::TYPE_BOOLEAN 	=> "parameter.type-boolean-error",
		self::TYPE_NULL		=> "parameter.type-null-error"
	];

	const PRIMITIVE_TYPES = [
		self::TYPE_STRING,
		self::TYPE_INTEGER,
		self::TYPE_FLOAT,
		self::TYPE_BOOLEAN,
		self::TYPE_NULL,
	];

	/** @var boolean */
	protected $hasDefault = false;
	
	/** @var string */
	protected $default;
	protected $name;

	/** @var Command */
	protected $command;

	/** @var int */
	protected $type = self::TYPE_STRING;
	protected $index = 0;

	/** @var mixed */
	protected $value;

	public function __construct(string $name, int $type = null, int $index = null) {
		$this->type = $type ?? $this->type;
		$this->name = $name;
		$this->index = $index ?? $this->index;
	}

	public function getIndex() : int {
		return $this->index;
	}

	public function setIndex(int $i) {
		$this->index = $i;
	}

	public function setValue($value) {
		$this->value = $value;
	}

	public function getValue() {
		return $this->value;
	}

	public function isRequired(CommandSender $sender = null) : bool {
		return !$this->isDefaultValueSet();
	}

	public function setDefaultValue(string $value) {
		$this->default = $value;
		$this->hasDefault = true;
		return $this;
	}

	public function isDefaultValueSet() : bool {
		return $this->hasDefault;
	}

	public function getDefaultValue() {
		return $this->default;
	}

	public function setCommand(Command $command) {
		$this->command = $command;
	}

	public function getName() {
		return $this->name;
	}

	public function getType() {
		return $this->type;
	}

	/**
	 * Will do checks only on primitive data types
	 * @return bool
	 */
	public static function validateInputType($input, int $type) : bool {
		if(!isset(self::PRIMITIVE_TYPES[$type])) return false;
		echo "Validating primitive type".PHP_EOL;
		switch ($type) {
			case self::TYPE_STRING:
				return is_string($input);
			case self::TYPE_BOOLEAN:
				switch(strtolower($input)) {
					case '1':
					case 'true':
					case 'yes':
					case 'y':
						return true;
					case '0':
					case 'false':
					case 'no':
					case 'n':
						return true;
					default:
						return false;
				}
				return false;
			case self::TYPE_DOUBLE:
			case self::TYPE_FLOAT:
				if(strpos($input, ".") === false) return false;
				return is_numeric($input);
			case self::TYPE_INTEGER:
				return is_numeric($input);
		}
		return false;
	}

	public function getTemplate(CommandSender $sender = null) {
		$out = $this->getName();
		if($this->isDefaultValueSet()) {
			$out .= "=".$this->getDefaultValue();
		}
		if($this->isRequired())
			$out = "<".$out.">";
		else
			$out = "[".$out."]";
		return $out;
	}

	public function createErrorMessage(CommandSender $sender, string $value) : Translatable {
		if(isset(self::$ERROR_MESSAGES[$this->type])) {
			return new Translatable(self::$ERROR_MESSAGES[$this->type], [
				"sender" => ($sender instanceof Player ? $sender->getDisplayName() : $sender->getName()),
				"value" => $value,
				"n" => $this->getIndex() + 1 // Must make this readable, not everyone can program
			]);
		} else {
			return new Translatable("argument.generic-error", [
				"sender" => ($sender instanceof Player ? $sender->getDisplayName() : $sender->getName()),
				"value" => $value,
				"n" => $this->getIndex() + 1
			]);
		}
	}

	public function isPrimitive() : bool {
		return isset(self::PRIMITIVE_TYPES[$this->type]);
	}

	/*
	 * ----------------------------------------------------------
	 * ABSTRACT
	 * ----------------------------------------------------------
	 */

	/**
	 * @param string $input
	 * @return mixed
	 */
	public function read(string $input, CommandSender $sender = null) {
		$silent = $sender ? false : true;
		if($this->isPrimitive()) {
			echo "Is primitive".PHP_EOL;
			if(!self::validateInputType($input, $this->type)) {
				echo "Input validation failed!".PHP_EOL;
				if(!$silent) {
					$sender->sendMessage($this->createErrorMessage($sender, $input));
				}
				return null;
			}
		}
		switch ($this->type) {
			case self::TYPE_STRING:
				return (string) $input;
			case self::TYPE_INTEGER:
				return (integer) $input;
			case self::TYPE_FLOAT:
				return (float) $input;
			case self::TYPE_BOOLEAN:
				return (bool) $input;
			default:
				break;
		}
		if(!$silent) {
			$sender->sendMessage($this->createErrorMessage($sender, $input));
		}
		return null;
	}

	public function isValid(string $input, CommandSender $sender = null) {
		return true;
	}

}