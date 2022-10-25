<?php

namespace App\MessageHandler;

use App\Message\SayHello;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SayHelloHandler implements MessageHandlerInterface
{
    public function __invoke(SayHello $sayHello)
    {
        dump($sayHello);
    }

}