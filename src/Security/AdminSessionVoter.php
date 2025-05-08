<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AdminSessionVoter extends Voter
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Only vote on ROLE_ADMIN attribute
        return $attribute === 'ROLE_ADMIN';
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        // Get the current session
        $session = $this->requestStack->getSession();
        
        // Check if the admin flag is set in the session
        if ($session->get('admin') === true) {
            return true;
        }
        
        // Otherwise, fall back to the standard role check
        $user = $token->getUser();
        if (!$user) {
            return false;
        }
        
        return in_array('ROLE_ADMIN', $user->getRoles());
    }
}
