<?php
namespace core\interfaces;


interface ListenerInterface
{
    public function handle($event): void;
}
