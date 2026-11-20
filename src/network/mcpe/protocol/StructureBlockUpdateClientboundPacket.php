<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\StructureEditorData;

/**
 * Clientbound variant of the structure block update packet, used to open the structure block editor.
 */
class StructureBlockUpdateClientboundPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::STRUCTURE_BLOCK_UPDATE_PACKET;

	public BlockPosition $blockPosition;
	public StructureEditorData $structureEditorData;
	public bool $isPowered = false;
	public bool $waterlogged = false;

	public static function create(BlockPosition $blockPosition, StructureEditorData $structureEditorData, bool $isPowered, bool $waterlogged) : self{
		$result = new self;
		$result->blockPosition = $blockPosition;
		$result->structureEditorData = $structureEditorData;
		$result->isPowered = $isPowered;
		$result->waterlogged = $waterlogged;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		throw new \LogicException("Clientbound structure block updates cannot be decoded");
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putBlockPosition($out, $this->blockPosition, $protocolId >= ProtocolInfo::PROTOCOL_1_26_10);
		CommonTypes::putStructureEditorData($out, $protocolId, $this->structureEditorData);
		CommonTypes::putBool($out, $this->isPowered);
		CommonTypes::putBool($out, $this->waterlogged);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return false;
	}
}
