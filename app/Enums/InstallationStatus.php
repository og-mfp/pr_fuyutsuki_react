<?php

namespace App\Enums;

enum InstallationStatus: string
{
    case Installed = 'installed';
    case NotInstalled = 'not_installed';
    case Unknown = 'unknown';
    case Inconsistent = 'inconsistent';
}
