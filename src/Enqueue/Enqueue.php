<?php

namespace Brain\Assets\Enqueue;

interface Enqueue
{
    /**
     * @return string
     */
    public function handle(): string;
}
