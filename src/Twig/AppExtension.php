<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('status_label', [$this, 'getStatusLabel']),
            new TwigFilter('status_class', [$this, 'getStatusClass']),
            new TwigFilter('status_icon', [$this, 'getStatusIcon']),
            new TwigFilter('status_description', [$this, 'getStatusDescription']),
        ];
    }

    public function getStatusLabel(string $status): string
    {
        $statuses = [
            'pending' => 'Pendiente',
            'in_progress' => 'En progreso',
            'completed' => 'Completado',
            'rejected' => 'Rechazado',
            'delayed' => 'Retrasado',
        ];

        return $statuses[$status] ?? 'Desconocido';
    }

    public function getStatusClass(string $status): string
    {
        $classes = [
            'pending' => 'bg-info bg-opacity-10 text-info border border-info',
            'in_progress' => 'bg-warning bg-opacity-10 text-warning border border-warning',
            'completed' => 'bg-success bg-opacity-10 text-success border border-success',
            'rejected' => 'bg-danger bg-opacity-10 text-danger border border-danger',
            'delayed' => 'bg-warning bg-opacity-20 text-warning border border-warning',
        ];

        return $classes[$status] ?? 'bg-secondary bg-opacity-10 text-secondary border border-secondary';
    }

    public function getStatusIcon(string $status): string
    {
        $icons = [
            'pending' => 'fa-clock',
            'in_progress' => 'fa-spinner fa-spin',
            'completed' => 'fa-check-circle',
            'rejected' => 'fa-times-circle',
            'delayed' => 'fa-clock',
        ];

        return $icons[$status] ?? 'fa-question-circle';
    }

    public function getStatusDescription(string $status): string
    {
        $descriptions = [
            'pending' => 'El ticket está esperando ser atendido',
            'in_progress' => 'El ticket está siendo atendido actualmente',
            'completed' => 'El ticket ha sido completado exitosamente',
            'rejected' => 'El ticket ha sido rechazado',
            'delayed' => 'El ticket ha sido retrasado',
        ];

        return $descriptions[$status] ?? 'Estado desconocido';
    }
}
