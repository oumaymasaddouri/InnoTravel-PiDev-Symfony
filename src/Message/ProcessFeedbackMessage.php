<?php

namespace App\Message;

class ProcessFeedbackMessage
{
    private int $feedbackId;
    
    public function __construct(int $feedbackId)
    {
        $this->feedbackId = $feedbackId;
    }
    
    public function getFeedbackId(): int
    {
        return $this->feedbackId;
    }
}