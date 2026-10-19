<?php

namespace App\Policies;

use App\Models\Debt;
use App\Models\User;

class DebtPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Debt $debt): bool
    {
        return (string) $debt->created_by === (string) $user->getKey()
            || ($debt->visibility === 'shared'
                && $debt->agreement_status === 'accepted'
                && in_array((string) $user->getKey(), [(string) $debt->creditor_id, (string) $debt->debtor_id], true));
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Debt $debt): bool
    {
        return $this->canEdit($user, $debt);
    }

    public function delete(User $user, Debt $debt): bool
    {
        return $this->canEdit($user, $debt);
    }

    private function canEdit(User $user, Debt $debt): bool
    {
        return $this->view($user, $debt)
            && ! in_array($debt->agreement_status, ['accepted', 'cancelled'], true)
            && ! $debt->agreementVersions()->exists()
            && ! $debt->payments()->exists();
    }
}
