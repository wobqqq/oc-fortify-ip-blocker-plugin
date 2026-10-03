<?php

declare(strict_types=1);

namespace Wobqqq\FortifyIpBlocker\Services;

use Backend;
use Backend\Widgets\Form;
use Wobqqq\Fortify\Dto\WidgetGroupItemDto;
use Wobqqq\Fortify\Enums\View;
use Wobqqq\Fortify\Enums\WidgetItemColor;
use Wobqqq\Fortify\Models\Fortify;
use Wobqqq\Fortify\Transformers\FortifyTransformer;
use Wobqqq\FortifyIpBlocker\Instances\IpBlockerDtoInstance;

final readonly class SettingsService
{
    public function fillWidgetGroupItem(WidgetGroupItemDto &$widgetGroupItemDto): void
    {
        $settingsLink = FortifyTransformer::widgetItemLinkDto(
            'wobqqq.fortify::lang.buttons.edit',
            Backend::url('system/settings/update/wobqqq/fortify/fortify#primarytab-ip-firewall'),
            'icon-wrench',
        );
        $ipBlockerDto = IpBlockerDtoInstance::instance()->get();
        $color = $ipBlockerDto->enabled ? WidgetItemColor::SUCCESS : WidgetItemColor::DANGER;
        $widgetGroupItemDto = FortifyTransformer::widgetGroupItemDto(
            'wobqqq.fortify::lang.fields.ip_blocker',
            [$settingsLink],
            $color,
            'icon-ban',
        );
    }

    public function applyDefaults(Fortify $fortify): void
    {
        $ipFirewall = is_array($fortify->ip_firewall) ? $fortify->ip_firewall : [];

        $ipFirewall['ip_blocker_enabled'] ??= false;
        $ipFirewall['ip_blocker_view'] ??= View::DENIED->value;

        $fortify->ip_firewall = $ipFirewall;
    }

    public function applyRules(Fortify $fortify): void
    {
        $fortify->attributeNames['ip_firewall.ip_blocker_ips.*.ip'] = 'wobqqq.fortify::lang.fields.ip';

        $fortify->rules['ip_firewall.ip_blocker_ips.*.ip'] = 'nullable|regex:/^[0-9a-fA-F\.:]+(\/\d{1,3})?$/|max:100';
        $fortify->rules['ip_firewall.ip_blocker_ips'] = 'nullable|ip_blocker_current_ip|array|max:150';
        $fortify->rules['ip_firewall.ip_blocker_view'] = 'required|string|max:100';
    }

    public function addFields(Form $form): void
    {
        $form->removeField('ip_firewall[ip_blocker_section]');
        $form->removeField('ip_firewall[ip_blocker_plugin]');
        $form->addTabFields([
            'ip_firewall[ip_blocker_section]' => [
                'label' => 'wobqqq.fortify::lang.fields.ip_blocker',
                'type' => 'section',
                'span' => 'full',
                'tab' => 'wobqqq.fortify::lang.tabs.ip_firewall',
            ],

            'ip_firewall[ip_blocker_enabled]' => [
                'label' => 'wobqqq.fortify::lang.fields.enabled',
                'span' => 'full',
                'type' => 'switch',
                'tab' => 'wobqqq.fortify::lang.tabs.ip_firewall',
                'default' => false,
                'comment' => 'wobqqq.fortify::lang.comments.ip_blocker_enabled',
                'commentHtml' => true,
            ],

            'ip_firewall[ip_blocker_view]' => [
                'label' => 'wobqqq.fortify::lang.fields.view',
                'span' => 'full',
                'required' => true,
                'type' => 'dropdown',
                'default' => 'wobqqq.fortify::denied',
                'tab' => 'wobqqq.fortify::lang.tabs.ip_firewall',
                'comment' => 'wobqqq.fortify::lang.comments.ip_blocker_view',
                'options' => 'getViewOptions',
                'trigger' => [
                    'action' => 'show',
                    'field' => 'ip_firewall[ip_blocker_enabled]',
                    'condition' => 'checked',
                ],
            ],

            'ip_firewall[ip_blocker_ips]' => [
                'label' => 'wobqqq.fortify::lang.fields.ips',
                'type' => 'datatable',
                'span' => 'full',
                'tab' => 'wobqqq.fortify::lang.tabs.ip_firewall',
                'adding' => true,
                'deleting' => true,
                'required' => true,
                'searching' => false,
                'recordsPerPage' => 20,
                'commentAbove' => 'wobqqq.fortify::lang.comments.ip_blocker_ips',
                'commentHtml' => true,
                'comment' => 'wobqqq.fortify::lang.comments.ip_blocker_ips_example',
                'trigger' => [
                    'action' => 'show',
                    'field' => 'ip_firewall[ip_blocker_enabled]',
                    'condition' => 'checked',
                ],
                'columns' => [
                    'ip' => [
                        'type' => 'string',
                        'title' => 'wobqqq.fortify::lang.fields.ip',
                    ],
                ],
            ],
        ]);
    }

    public function removeEmptyRows(Fortify $fortify): void
    {
        $ipFirewall = $fortify->ip_firewall;

        if (!is_array($ipFirewall) || !is_array($ipFirewall['ip_blocker_ips'] ?? null)) {
            return;
        }

        $ipFirewall['ip_blocker_ips'] = array_values(array_filter(
            $ipFirewall['ip_blocker_ips'],
            static fn (mixed $row): bool => is_array($row) && is_scalar($row['ip'] ?? null) && trim((string)$row['ip']) !== '',
        ));

        $fortify->ip_firewall = $ipFirewall;
    }
}
