<x-filament-widgets::widget>
    <x-filament::section>
        <div class="fi-filament-info-widget">
            <h2 class="fi-account-widget-heading">
                {!! $header !!}
            </h2>
            <p class="fi-account-widget-user-name">
                {!! $vehicle->make !!} &nbsp; {!! $vehicle->model !!} &nbsp; {!! $vehicle->year !!}
            </p>
            <div class="fi-ta-row">
                <p class="fi-ta-cell"><label class="fi-ta-cell-label">{{trans("ev5::ev.odo")}}:&nbsp;</label><span>{{$vehicle->odo}}km</span></p>
                <p class="fi-ta-cell"><label class="fi-ta-cell-label">{{trans("ev5::ev.soc")}}:&nbsp;</label><span>{{$vehicle->soc}}%</span></p>
            </div>

        </div>
    </x-filament::section>
</x-filament-widgets::widget>
