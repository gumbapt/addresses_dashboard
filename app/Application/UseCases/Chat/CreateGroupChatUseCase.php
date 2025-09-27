<?php

namespace App\Application\UseCases\Chat;

use App\Domain\Entities\Chat;
use App\Domain\Entities\ChatUser;
use App\Domain\Repositories\ChatRepositoryInterface;

class CreateGroupChatUseCase
{
    public function __construct(private ChatRepositoryInterface $chatRepository) {}

    /**
     * @param ChatUser $creator
     * @param string $name
     * @param string|null $description
     * @param array $participants // Array de ChatUser
     * @return Chat
     */
    public function execute(ChatUser $creator, string $name, ?string $description, array $participants): Chat
    {
        // Garante que o criador está na lista de participantes
        $allParticipants = array_merge($participants, [$creator]);
        
        $chatData = $this->chatRepository->createGroupChat($name, $description ?? '', $creator);
        
        // Adiciona participantes
        foreach ($allParticipants as $participant) {
            $this->chatRepository->addParticipantToChat($chatData['id'], $participant);
        }
        
        // Converte dados em entidade de domínio
        return new Chat(
            id: $chatData['id'],
            name: $chatData['name'],
            type: $chatData['type'],
            description: $chatData['description'],
            createdBy: $chatData['created_by'],
            createdByType: $chatData['created_by_type'],
            createdAt: $chatData['created_at'] ? new \DateTime($chatData['created_at']) : null,
            updatedAt: $chatData['updated_at'] ? new \DateTime($chatData['updated_at']) : null
        );
    }
} 