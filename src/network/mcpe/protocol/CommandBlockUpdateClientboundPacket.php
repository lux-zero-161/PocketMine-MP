<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\BlockPosition;

/**
 * Clientbound variant of the command block update packet, used to open the command block editor on the client.
 *
 * The library packet with the same name is serverbound-only, so this class only implements encoding.
 */
class CommandBlockUpdateClientboundPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::COMMAND_BLOCK_UPDATE_PACKET;

	public BlockPosition $blockPosition;
	public int $commandBlockMode = 0;
	public bool $isRedstoneMode = true;
	public bool $isConditional = false;
	public string $command = "";
	public string $lastOutput = "";
	public string $name = "";
	public string $filteredName = "";
	public bool $shouldTrackOutput = true;
	public int $tickDelay = 0;
	public bool $executeOnFirstTick = true;

	public static function create(
		BlockPosition $blockPosition,
		int $commandBlockMode,
		bool $isRedstoneMode,
		bool $isConditional,
		string $command,
		string $lastOutput,
		string $name,
		bool $shouldTrackOutput,
		int $tickDelay,
		bool $executeOnFirstTick
	) : self{
		$result = new self;
		$result->blockPosition = $blockPosition;
		$result->commandBlockMode = $commandBlockMode;
		$result->isRedstoneMode = $isRedstoneMode;
		$result->isConditional = $isConditional;
		$result->command = $command;
		$result->lastOutput = $lastOutput;
		$result->name = $name;
		$result->filteredName = $name;
		$result->shouldTrackOutput = $shouldTrackOutput;
		$result->tickDelay = $tickDelay;
		$result->executeOnFirstTick = $executeOnFirstTick;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		throw new \LogicException("Clientbound command block updates cannot be decoded");
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putBool($out, true);
		CommonTypes::putBlockPosition($out, $this->blockPosition, $protocolId >= ProtocolInfo::PROTOCOL_1_26_10);
		VarInt::writeUnsignedInt($out, $this->commandBlockMode);
		CommonTypes::putBool($out, $this->isRedstoneMode);
		CommonTypes::putBool($out, $this->isConditional);
		CommonTypes::putString($out, $this->command);
		CommonTypes::putString($out, $this->lastOutput);
		CommonTypes::putString($out, $this->name);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_60){
			CommonTypes::putString($out, $this->filteredName);
		}
		CommonTypes::putBool($out, $this->shouldTrackOutput);
		LE::writeUnsignedInt($out, $this->tickDelay);
		CommonTypes::putBool($out, $this->executeOnFirstTick);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return false;
	}
}
