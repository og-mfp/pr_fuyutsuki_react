<?php

return [
    'admin' => ['admin.access'],
    'users' => ['users.view', 'users.create', 'users.update', 'users.delete'],
    'groups' => ['groups.view', 'groups.create', 'groups.update', 'groups.delete'],
    'permissions' => ['permissions.view', 'permissions.assign'],
    'audit' => ['audit.view', 'audit.export', 'audit.manage-retention'],
];
