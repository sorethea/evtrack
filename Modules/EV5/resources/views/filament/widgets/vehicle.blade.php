<x-filament-widgets::widget>
    <x-filament::section>
        <div class="fi-filament-info-widget">
            <h2 class="fi-account-widget-heading">
                {!! $header !!}
            </h2>
            <p class="fi-account-widget-user-name">
                {!! $vehicle->make !!} &nbsp; {!! $vehicle->model !!} &nbsp; {!! $vehicle->year !!}
            </p>
            <ul class="fi-dropdown-list">
                <li><label>{{trans("ev5::ev.odo")}}</label>:&nbsp;{{$vehicle->odo}}km</li>
                <li><label>{{trans("ev5::ev.soc")}}</label>:&nbsp;{{$vehicle->soc}}%</li>
            </ul>

        </div>
    </x-filament::section>
</x-filament-widgets::widget>
