<?php

namespace App\Game\Application\Query;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class GetLastGamesHandler implements MessageHandlerInterface
{
    public function __invoke(GetLastGames $getLastGames)
    {

    }
}