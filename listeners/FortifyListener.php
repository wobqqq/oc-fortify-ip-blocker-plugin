<?php

declare(strict_types=1);

namespace Wobqqq\FortifyIpBlocker\Listeners;

use Backend;
use Backend\Widgets\Form;
use October\Rain\Events\Dispatcher;
use System\Controllers\Settings;
use Wobqqq\Fortify\Dto\WidgetGroupItemDto;
use Wobqqq\Fortify\Enums\FortifyEvent;
use Wobqqq\Fortify\Enums\View;
use Wobqqq\Fortify\Enums\WidgetItemColor;
use Wobqqq\Fortify\Models\Fortify;
use Wobqqq\Fortify\Transformers\FortifyTransformer;
use Wobqqq\FortifyIpBlocker\Cache\IpBlockerDtoCache;
use Wobqqq\FortifyIpBlocker\Instances\IpBlockerDtoInstance;

final readonly class FortifyListener
{
    public function __construct(
        private IpBlockerDtoCache $ipBlockerDtoCache,
    ) {
    }

    /**
     * @param Dispatcher $event
     */
    public function subscribe($event): void
    {
        $event->listen(FortifyEvent::SERVICES_WIDGET_GROUP_ITEM_IP_BLOCKER->value, function (WidgetGroupItemDto &$widgetGroupItemDto): void {
            $this->serveWidgetGroupItem($widgetGroupItemDto);
        });

        $event->listen(FortifyEvent::MODEL_FORTIFY_INIT_SETTINGS_DATA->value, function (Fortify &$fortify): void {
            $this->serveModelInitSettingsData($fortify);
        });

        Fortify::extend(function (Fortify $fortify): void {
            $this->serveModel($fortify);
        });

        // Model events, not bindEvent(): the settings instance may predate this listener.
        $event->listen('eloquent.saving: ' . Fortify::class, function (Fortify $fortify): void {
            $this->filterEmptyIps($fortify);
        });

        $event->listen(
            ['eloquent.saved: ' . Fortify::class, 'eloquent.deleted: ' . Fortify::class],
            function (): void {
                $this->ipBlockerDtoCache->clear();
            },
        );

        $event->listen('backend.form.extendFields', function (Form $form): void {
            if (!$form->getController() instanceof Settings || !$form->model instanceof Fortify || $form->isNested) {
                return;
            }

            $this->serveModel($form->model);
            $this->serveModelInitSettingsData($form->model);
            $this->serveFields($form);
        });
    }

    private function serveWidgetGroupItem(WidgetGroupItemDto &$widgetGroupItemDto): void
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

    private function serveModelInitSettingsData(Fortify $fortify): void
    {
        $ipFirewall = is_array($fortify->ip_firewall) ? $fortify->ip_firewall : [];

        $ipFirewall['ip_blocker_enabled'] ??= false;
        $ipFirewall['ip_blocker_view'] ??= View::DENIED->value;

        $fortify->ip_firewall = $ipFirewall;
    }

    private function serveModel(Fortify $fortify): void
    {
        $fortify->attributeNames['ip_firewall.ip_blocker_ips.*.ip'] = 'wobqqq.fortify::lang.fields.ip';

        $fortify->rules['ip_firewall.ip_blocker_ips.*.ip'] = 'nullable|regex:/^[0-9a-fA-F\.:]+(\/\d{1,3})?$/|max:100';
        $fortify->rules['ip_firewall.ip_blocker_ips'] = 'nullable|ip_blocker_current_ip|array|max:150';
        $fortify->rules['ip_firewall.ip_blocker_view'] = 'required|string|max:100';
    }

    private function serveFields(Form $form): void
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

    private function filterEmptyIps(Fortify $fortify): void
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
