<?php

namespace App\Enums;

enum RoleEnum: string
{
    case ADMIN = 'Administrador';
    case SERVICIO_SOCIAL = 'Servicio Social';
    case SERVICIO_TIPO = 'Servicio';

    public const PERM_ACCESS_ADMIN = 'access_admin_panel';

    public const PERM_ACCESS_SERVICIO = 'access_servicio_social_panel';
}
