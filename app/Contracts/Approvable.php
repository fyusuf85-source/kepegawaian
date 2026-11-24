<?php

namespace App\Contracts;

use App\Models\User;

interface Approvable
{
    public function approvalFlowCode(): string;

    public function approvalLabel(): string;

    public function approvalOrgUnitId(): ?int;

    public function markApprovalPending(): void;

    public function markApprovalApproved(User $actor): void;

    public function markApprovalRejected(User $actor, string $reason): void;
}
