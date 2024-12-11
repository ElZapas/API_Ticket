<?php

namespace enums;

enum TicketPrioridad: string {
    case BAJA = 'Baja';
    case MEDIA = 'Media';
    case ALTA = 'Alta';
    case CRITICA = 'Crítica';
}