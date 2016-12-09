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
namespace dominate;

use pocketmine\command\Command as PocketMineCommand;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\command\CommandSender;

class Command extends PocketMineCommand implements PluginIdentifiableCommand {

	/**
	 * @var Requirement[]
	 */
	protected $requirements = [];

	/**
	 * @var Command[]
	 */
	protected $childs = [];

	/**
	 * @var Argument[]
	 */
	protected $arguments = [];

	/**
	 * Values from each argument
	 * @var mixed[]
	 */
	protected $values = [];

	/**
	 * @var string[]
	 */
	protected $aliases = [];

	/**
	 * @var Command
	 */
	protected $endPoint = null;

	// Last execution parameters
	
	/** @var CommandSender */
	protected $sender;

	/** @var string[] */
	protected $args = [];

	/** @var string */
	protected $label;

	/*
	 * ----------------------------------------------------------
	 * CHILD (Sub Command)
	 * ----------------------------------------------------------
	 */

	/**
	 * Registers new subcommand or replaces existing one
	 * 
	 * @param Command $command
	 * @param int $index if null then the child will be added at the end of array
	 */
	public function addChild(Command $command, int $index = null) {
		if($this->contains($command)) 
			throw new \InvalidArgumentException("command '{$command->getName()}' is already a child of '{$this->getName()}'");
		if($command->contains($this))
			throw new \LogicException("parent '{$command->getName()}' can't be child of child");
		$this->childs[($index ?? count($this->childs))] = $command;
		$command->setParent($this);
	}

	/**
	 * @var Command[]
	 */
	public function addChilds(array $childs) {
		foreach($childs as $child) {
			$this->addChild($child);
		}
	}

	/**
	 * @var Command[]
	 */
	public function setChilds(array $childs) {
		foreach($this->childs as $child) {
			$this->removeChild($child);
		}
		foreach($childs as $child) {
			$this->addChild($child);
		}
	}

	public function contains(Command $command) : bool {
		return in_array($command, $this->childs, true);
	}

	public function removeChild(Command $command) {
		if($this->contains($command)) {
			unset($this->childs[array_search($command, $this->childs, true)]);
			$command->setParent(null);
		}
	}

	public function getChilds() : array {
		return $this->childs;
	}

	/**
	 * Returns a sub-command(s) matching given $token
	 * @param string $token
	 */
	public function getChildByToken(string $token) {
		$match = [];
		foreach ($this->getChilds() as $key => $value) {
			if(strtolower($value->getName()) === strtolower($token)) $match[] = $match;

		}
	}

	public function isChild() : bool {
		return $this->parent instanceof Command;
	}

	public function isParent() : bool {
		return !empty($this->childs);
	}

	/*
	 * ----------------------------------------------------------
	 * ARGUMENTS
	 * ----------------------------------------------------------
	 */

	public function addArgument(Argument $arg) {
		$this->arguments[] = $argument;
	}

	public function removeArgument(Argument $arg) {
		if(($i = $this->getArgumentIndex($arg)) >= 0) {
			unset($this->arguments[$i]);
		}
	}

	public function getArgumentIndex(Argument $arg) : int {
		foreach($this->arguments as $i => $a) {
			if($a === $arg) return $i;
		}
		return -1;
	}

	public function readArgument(int $index, string $input = null) {
		// What a useless function is here :/
		if(isset($this->values[$index])) return $this->values[$index];
		if($input) {
			if(isset($this->arguments[$index])) {
				$this->values[$index] = $this->arguments[$index]->read($input);
			}
		}
	}

	public function readArguments() {
		foreach ($this->arguments as $key => $arg) {
			if(!isset($this->args[$key])) return false;
			$this->readArgument($index, $this->args[$key]);
		}
		return true;
	}

	/*
	 * ----------------------------------------------------------
	 * EXECUTION
	 * ----------------------------------------------------------
	 */

	public function execute(CommandSender $sender, $label, array $args) {
		$this->sender 	= $sender;
		$this->label 	= "/" . $this->getName() . " " . implode(" ", $args);
		$this->args 	= $args;

		if(!$this->testPermission($sender)) {
			$sender->sendMessage($this->getPermissionMessage());
			return false;
		}
		if(!$this->testRequirements()) {
			return false;
		}
		if( ($argCount = count($args)) < $this->getRequiredArgumentCount() ) {
            $this->sendUsage($sender);
            return false;
        }

        $stop = false;
        foreach ($this->arguments as $i => $param) {

            if (!isset($args[$i]) and !$param->isDefaultValueSet()) {
            	break;
            }

            $value = isset($args[$i]) ? $args[$i] : $param->getDefaultValue();

            if($param->getType()->isValid($sender, $value)) {
                $param->setValue($param->read($value, $sender, false));
            } else {
                $stop = true;
                $sender->sendMessage($param->createErrorMessage($sender, $value));
                break;
            }
            $this->values[$i] = $value;
        }
        if($stop) return false;

        if (!empty($this->childs) and count($this->values) > 0) {
            if($this->values[0] === "") {
                $this->sendUsage($sender);
                return false;
            }
            $matches = $this->getChildByToken((string) $this->values[0]);

            if( ($matchCount = count($matches)) === 1 ) {
                array_shift($args);
                $matches[0]->execute($sender, $label, $args);
                $this->endPoint = $matches[0]->endPoint;
                return true;
            } else {
            	// Token was too ambiguous
                if($matchCount > 8) {
                    $sender->sendMessage(Localizer::trans("command.too-ambiguous", [$this->values[0])]);
                    return false;
                }
                // No commands by token was found
                if($matchCount === 0) {
                    $sender->sendMessage(Localizer::trans("command.child-none", [$this->values[0])]);
                    return false;
                }
                // Few commands by token was found an suggestion table will be created
                $sender->sendMessage(Localizer::trans("command.suggestion.header", [$this->values[0])]);
                foreach($matches as $match) {
                    $sender->sendMessage(Localizer::trans("command.suggestion", [$match->getName(), $match->getUsage($sender), $match->getDescription()]));
                }
                return false;
            }
        }

        $this->reset();
        return true;
	}

	public function reset() {
		$this->sender = null;
		$this->label = "";
		$this->args = [];
		$this->values = [];
	}

}