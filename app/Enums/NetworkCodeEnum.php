<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum NetworkCodeEnum: string implements HasLabel
{
    case Approved = '00';
    case ReferToClient = '01';
    case TimeoutExceeded = '05';
    case InvalidTransaction = '12';
    case InvalidAmount = '13';
    case InvalidReceiverPhone = '14';
    case FormatError = '30';
    case ServiceInactive = '41';
    case TokenInvalid = '55';
    case PhoneMismatch = '56';
    case ReceiverRejected = '57';
    case RestrictedAccount = '62';
    case LateResponse = '68';
    case InvalidID = '80';
    case Timeout = '87';
    case BankClosure = '90';
    case InstitutionUnavailable = '91';
    case NonAffiliatedBank = '92';
    case NotificationError = '99';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Approved => 'APROBADO',
            self::ReferToClient => 'REFERIRSE AL CLIENTE',
            self::TimeoutExceeded => 'TIEMPO DE RESPUESTA EXCEDIDO',
            self::InvalidTransaction => 'TRANSACCIÓN INVÁLIDA',
            self::InvalidAmount => 'MONTO INVÁLIDO',
            self::InvalidReceiverPhone => 'NÚMERO TELÉFONO RECEPTOR ERRADO',
            self::FormatError => 'ERROR DE FORMATO',
            self::ServiceInactive => 'SERVICIO NO ACTIVO',
            self::TokenInvalid => 'TOKEN INVÁLIDO',
            self::PhoneMismatch => 'CELULAR NO COINCIDE',
            self::ReceiverRejected => 'NEGADA POR EL RECEPTOR',
            self::RestrictedAccount => 'CUENTA RESTRINGIDA',
            self::LateResponse => 'RESPUESTA TARDÍA, PROCEDE REVERSO',
            self::InvalidID => 'CÉDULA O PASAPORTE ERRADO',
            self::Timeout => 'TIME OUT',
            self::BankClosure => 'CIERRE BANCARIO EN PROCESO',
            self::InstitutionUnavailable => 'INSTITUCIÓN NO DISPONIBLE',
            self::NonAffiliatedBank => 'BANCO RECEPTOR NO AFILIADO',
            self::NotificationError => 'ERROR EN NOTIFICACIÓN',
        };
    }
}
