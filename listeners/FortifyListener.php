<?php

declare(strict_types=1);

namespace Wobqqq\FortifyIpBlocker\Listeners;

use Backend\Widgets\Form;
use October\Rain\Events\Dispatcher;
use System\Controllers\Settings;
use Wobqqq\Fortify\Dto\WidgetGroupItemDto;
use Wobqqq\Fortify\Enums\FortifyEvent;
use Wobqqq\Fortify\Models\Fortify;
use Wobqqq\FortifyIpBlocker\Cache\IpBlockerDtoCache;
use Wobqqq\FortifyIpBlocker\Services\SettingsService;

final readonly class FortifyListener
{
    public function __construct(
        private SettingsService $settingsService,
        private IpBlockerDtoCache $ipBlockerDtoCache,
    ) {
    }

    /**
     * @param Dispatcher $event
     */
    public function subscribe($event): void
    {
        $event->listen(FortifyEvent::SERVICES_WIDGET_GROUP_ITEM_IP_BLOCKER->value, function (WidgetGroupItemDto &$widgetGroupItemDto): void {
            $this->settingsService->fillWidgetGroupItem($widgetGroupItemDto);
        });

        $event->listen(FortifyEvent::MODEL_FORTIFY_INIT_SETTINGS_DATA->value, function (Fortify &$fortify): void {
            $this->settingsService->applyDefaults($fortify);
        });

        Fortify::extend(function (Fortify $fortify): void {
            $this->settingsService->applyRules($fortify);
        });

        // Model events, not bindEvent(): the settings instance may predate this listener.
        $event->listen('eloquent.saving: ' . Fortify::class, function (Fortify $fortify): void {
            $this->settingsService->removeEmptyRows($fortify);
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

            $this->settingsService->applyRules($form->model);
            $this->settingsService->applyDefaults($form->model);
            $this->settingsService->addFields($form);
        });
    }
}
