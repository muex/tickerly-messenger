<?php

namespace App\Game\Application\Query;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
class GetLastGamesHandler
{
    public function __invoke(GetLastGames $getLastGames)
    {

    }
}