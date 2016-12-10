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
namespace dominate\argument;

use pocketmine\command\CommandSender;

class Argument {

	const TYPE_STRING = 0x0;
	const TYPE_INTEGER = 0x1;
	const TYPE_FLOAT = 0x2;
	const TYPE_DOUBLE = 0x3;
	const TYPE_BOOLEAN = 0x4;

	/** @var boolean */
	protected $hasDefault = false;
	
	/** @var string */
	protected $default;
	protected $name;

	/** @var Command */
	protected $command;

	/** @var int */
	protected $type = self::TYPE_STRING;

	/** @var mixed */
	protected $value;

	public function __construct(string $name, int $type = null) {
		$this->type = $type ?? $this->type;
		$this->name = $name;
	}

	public function setValue($value) {
		$this->value;
	}

	public function getValue() {
		return $this->value;
	}

	public function isRequired(CommandSender $sender = null) : bool {
		return !$this->isDefaultValueSet();
	}

	public function setDefaultValue(string $value) {
		$this->default = $default;
		$this->hasDefault = true;
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

	public static function validateInputType(string $input, int $type) : bool {
		switch ($type) {
			case self::TYPE_STRING:
				return is_string((string) $input);
			case self::TYPE_BOOLEAN:
				return is_bool((bool) $input);
			case self::TYPE_DOUBLE:
			case self::TYPE_FLOAT:
				return is_float((float) $input);
			case self::TYPE_INTEGER:
				return is_int((float) $input);
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
		return $input;
	}

	public function isValid(string $input, CommandSender $sender = null) {
		return true;
	}

}