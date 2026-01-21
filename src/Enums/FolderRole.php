<?php

namespace Platform\Notes\Enums;

enum FolderRole: string {
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case MEMBER = 'member';
    case VIEWER = 'viewer';
}
