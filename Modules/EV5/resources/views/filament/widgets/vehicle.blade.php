<x-filament-widgets::widget>
    <x-filament::section>
        <div class="fi-filament-info-widget">
            <h2 class="fi-account-widget-heading">
                {!! $header !!}
            </h2>
            <p class="fi-account-widget-user-name">
                {!! $vehicle->make !!} &nbsp; {!! $vehicle->model !!} &nbsp; {!! $vehicle->year !!}
            </p>
            <p class="fi-section-content">
                <label>{{trans("ev5::ev.odo")}}:&nbsp;{{$vehicle->odo}}km</label>
                <label>{{trans("ev5::ev.soc")}}:&nbsp;{{$vehicle->soc}}km</label>
            </p>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
