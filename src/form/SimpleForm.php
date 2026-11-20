<?php

declare(strict_types=1);

namespace pocketmine\form;

use pocketmine\player\Player;
use function count;
use function is_int;

/**
 * Basic button-list form implementation for core gameplay UIs.
 */
class SimpleForm implements Form{
	/** @var array<int, array{text: string, image?: array{type: string, data: string}}> */
	private array $buttons = [];
	/** @var \Closure(Player, ?int) : void */
	private \Closure $onSubmit;

	/**
	 * @param \Closure(Player, ?int) : void $onSubmit
	 */
	public function __construct(
		private string $title,
		private string $content,
		\Closure $onSubmit
	){
		$this->onSubmit = $onSubmit;
	}

	public function addButton(string $text, ?string $imagePath = null) : self{
		$button = ["text" => $text];
		if($imagePath !== null){
			$button["image"] = ["type" => "path", "data" => $imagePath];
		}
		$this->buttons[] = $button;
		return $this;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function jsonSerialize() : array{
		return [
			"type" => "form",
			"title" => $this->title,
			"content" => $this->content,
			"buttons" => $this->buttons
		];
	}

	public function handleResponse(Player $player, $data) : void{
		if($data === null){
			($this->onSubmit)($player, null);
			return;
		}
		if(!is_int($data) || $data < 0 || $data >= count($this->buttons)){
			throw new FormValidationException("Expected a valid button index");
		}
		($this->onSubmit)($player, $data);
	}
}
