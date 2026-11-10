<?php

namespace App\Filament\Resources;

use App\Enums\RoleEnum;
use App\Filament\Resources\InventarioResource\Pages;
use App\Models\Inventario;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class InventarioResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Inventario::class;

    protected static ?string $recordTitleAttribute = 'num_serie';

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Inventario';

    protected static ?string $modelLabel = 'registro de inventario';

    protected static ?string $pluralModelLabel = 'registros de inventario';

    protected static ?string $navigationGroup = 'Gestión de inventario';

    protected static ?int $navigationSort = 1;

    public static function hasRecordTitle(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Identificación y ubicación')
                        ->icon('heroicon-m-identification')
                        ->schema([
                            Forms\Components\TextInput::make('num_serie')
                                ->label('Número de serie')
                                ->prefixIcon('heroicon-m-qr-code')
                                ->placeholder('Ej. SN-12345')
                                ->maxLength(100)
                                ->columnSpan(1),
                            Forms\Components\Select::make('id_producto')->placeholder('Selecciona...')
                                ->label('Material / equipo')
                                ->relationship('material', 'nombre')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->prefixIcon('heroicon-m-cube')
                                ->columnSpan(2),
                            Forms\Components\ToggleButtons::make('estado')->label('Estado')
                                ->options([
                                    'Disponible' => 'Disponible',
                                    'Asignado' => 'Asignado',
                                    'En Mantenimiento' => 'En Mantenimiento',
                                    'Dañado' => 'Dañado',
                                    'Baja' => 'Baja',
                                    'Devuelto a Proveedor' => 'Devuelto a Proveedor',
                                ])
                                ->colors([
                                    'Disponible' => 'success',
                                    'Asignado' => 'warning',
                                    'En Mantenimiento' => 'gray',
                                    'Dañado' => 'danger',
                                    'Baja' => 'gray',
                                    'Devuelto a Proveedor' => 'info',
                                ])
                                ->inline()
                                ->default('Disponible')
                                ->required()
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('ubicacion_fisica')
                                ->label('Ubicación física')
                                ->prefixIcon('heroicon-m-building-office')
                                ->placeholder('Ej. Laboratorio 1, Edificio B')
                                ->maxLength(150)
                                ->columnSpan(2),
                            Forms\Components\ToggleButtons::make('tipo_propiedad')
                                ->options([
                                    'Propio' => 'Propio',
                                    'Rentado' => 'Rentado',
                                ])
                                ->colors([
                                    'Propio' => 'primary',
                                    'Rentado' => 'gray',
                                ])
                                ->inline()
                                ->default('Propio')
                                ->live()
                                ->required()
                                ->columnSpan(1),
                            Forms\Components\Select::make('id_usuario')->placeholder('Selecciona...')
                                ->label('Registrado por (usuario)')
                                ->relationship('usuario', 'name')
                                ->default(auth()->id())
                                ->required()
                                ->searchable()
                                ->preload()
                                ->prefixIcon('heroicon-m-user')
                                ->columnSpanFull(),
                        ])->columns(3),

                    Forms\Components\Wizard\Step::make('Factura y proveedor')
                        ->icon('heroicon-m-document-text')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Select::make('id_proveedor')->placeholder('Selecciona...')
                                        ->label('Proveedor')
                                        ->relationship('proveedor', 'nombre_empresa')
                                        ->searchable()
                                        ->preload()
                                        ->prefixIcon('heroicon-m-truck')
                                        ->visible(fn (Forms\Get $get) => $get('tipo_propiedad') === 'Rentado')
                                        ->createOptionForm([
                                            Forms\Components\TextInput::make('nombre_empresa')
                                                ->label('Nombre de la empresa')
                                                ->required()
                                                ->maxLength(150),
                                            Forms\Components\TextInput::make('rfc')
                                                ->label('Rfc')
                                                ->maxLength(13),
                                            Forms\Components\TextInput::make('contacto_nombre')
                                                ->label('Nombre del contacto')
                                                ->maxLength(150),
                                            Forms\Components\TextInput::make('contacto_telefono')
                                                ->label('Teléfono del contacto')
                                                ->tel()
                                                ->maxLength(20),
                                            Forms\Components\TextInput::make('contacto_email')
                                                ->label('Email del contacto')
                                                ->email()
                                                ->maxLength(100),
                                        ]),
                                    Forms\Components\TextInput::make('num_factura')
                                        ->label('Número de factura')
                                        ->prefixIcon('heroicon-m-hashtag')
                                        ->maxLength(100)
                                        ->visible(fn (Forms\Get $get) => $get('tipo_propiedad') === 'Rentado'),
                                    Forms\Components\DatePicker::make('fecha_factura')
                                        ->label('Fecha de factura')
                                        ->prefixIcon('heroicon-m-calendar')
                                        ->visible(fn (Forms\Get $get) => $get('tipo_propiedad') === 'Rentado'),
                                ]),
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\DatePicker::make('fecha_inicio_renta')
                                        ->label('Inicio de renta')
                                        ->prefixIcon('heroicon-m-calendar-days'),
                                    Forms\Components\DatePicker::make('fecha_fin_renta')
                                        ->label('Fin de renta')
                                        ->prefixIcon('heroicon-m-calendar-days'),
                                    Forms\Components\Textarea::make('observaciones_renta')
                                        ->label('Observaciones de renta')
                                        ->columnSpanFull(),
                                ])
                                ->visible(fn (Forms\Get $get) => $get('tipo_propiedad') === 'Rentado'),
                        ]),

                    Forms\Components\Wizard\Step::make('Garantía y observaciones')
                        ->icon('heroicon-m-clipboard-document-check')
                        ->schema([

                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\DatePicker::make('fecha_registro')
                                        ->label('Fecha de registro')
                                        ->default(now())
                                        ->required()
                                        ->prefixIcon('heroicon-m-clock'),
                                    Forms\Components\DatePicker::make('garantia_fecha_fin')
                                        ->label('Fin de garantía')
                                        ->prefixIcon('heroicon-m-shield-check'),
                                    Forms\Components\Select::make('garantia_estado')->placeholder('Selecciona...')
                                        ->label('Estado de garantía')
                                        ->options([
                                            'vigente' => 'Vigente',
                                            'vencida' => 'Vencida',
                                            'sin_garantia' => 'Sin Garantía',
                                        ])
                                        ->default('sin_garantia'),
                                ]),

                            Forms\Components\Grid::make(1)
                                ->schema([
                                    Forms\Components\Select::make('estado_registro')->placeholder('Selecciona...')
                                        ->label('Estado de registro')
                                        ->options([
                                            'Pendiente' => 'Pendiente',
                                            'Aprobado' => 'Aprobado',
                                            'Rechazado' => 'Rechazado',
                                        ])
                                        ->default('Pendiente')
                                        ->required()
                                        ->visible(fn () => auth()->user() && auth()->user()->tipo_usuario === RoleEnum::ADMIN->value),
                                    Forms\Components\Textarea::make('observaciones_generales')
                                        ->label('Observaciones generales')
                                        ->rows(2),
                                ]),
                        ]),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('num_serie')->wrap(false)->limit(30)->grow(false)
                    ->label('No. serie')
                    ->fontFamily('mono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('material.nombre')->wrap(false)->limit(30)->grow(false)
                    ->label('Material')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('material.marca.nombre')->wrap(false)->limit(30)->grow(false)
                    ->label('Marca')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('material.tipo.nombre')->wrap(false)->limit(30)->grow(false)
                    ->label('Tipo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ubicacion_fisica')->wrap(false)->limit(30)->grow(false)
                    ->label('Ubicación')
                    ->searchable(),
                Tables\Columns\TextColumn::make('estado')->wrap(false)->limit(30)->grow(false)->label('Estado')
                    ->badge()
                    ->colors([
                        'success' => 'Disponible',
                        'warning' => 'Asignado',
                        'gray' => ['En Mantenimiento', 'Baja'],
                        'danger' => 'Dañado',
                        'info' => 'Devuelto a Proveedor',
                    ])
                    ->icons([
                        'heroicon-m-check-circle' => 'Disponible',
                        'heroicon-m-user' => 'Asignado',
                        'heroicon-m-wrench' => 'En Mantenimiento',
                        'heroicon-m-exclamation-triangle' => 'Dañado',
                        'heroicon-m-minus-circle' => 'Baja',
                        'heroicon-m-arrow-path' => 'Devuelto a Proveedor',
                    ]),
                Tables\Columns\TextColumn::make('tipo_propiedad')->wrap(false)->limit(30)->grow(false)
                    ->label('Propiedad')
                    ->badge()
                    ->colors([
                        'primary' => 'Propio',
                        'gray' => 'Rentado',
                    ])
                    ->icons([
                        'heroicon-m-building-library' => 'Propio',
                        'heroicon-m-currency-dollar' => 'Rentado',
                    ]),
                Tables\Columns\TextColumn::make('aprobado')->wrap(false)->limit(30)->grow(false)
                    ->label('Aprobado')
                    ->formatStateUsing(fn ($state) => $state ? 'Aprobado' : 'Pendiente')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'warning')
                    ->icon(fn ($state) => $state ? 'heroicon-m-shield-check' : 'heroicon-m-clock'),
                Tables\Columns\TextColumn::make('aprobadoPor.name')->wrap(false)->limit(30)->grow(false)
                    ->label('Aprobado por')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('fecha_aprobacion')->wrap(false)->limit(30)->grow(false)
                    ->label('Fecha aprobación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)->label('Fecha de creación')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->defaultPaginationPageOption(10)
            ->filters([
                TrashedFilter::make(),
                Tables\Filters\TernaryFilter::make('aprobado')
                    ->label('Estado de aprobación'),
                Tables\Filters\SelectFilter::make('estado')->label('Estado')
                    ->options([
                        'Disponible' => 'Disponible',
                        'Asignado' => 'Asignado',
                        'En Mantenimiento' => 'En Mantenimiento',
                        'Dañado' => 'Dañado',
                        'Baja' => 'Baja',
                        'Devuelto a Proveedor' => 'Devuelto a Proveedor',
                    ]),
                Tables\Filters\SelectFilter::make('tipo_propiedad')
                    ->options([
                        'Propio' => 'Propio',
                        'Rentado' => 'Rentado',
                    ]),
            ])

            ->emptyStateHeading('No hay registros')
            ->emptyStateDescription('Cuando se creen registros, aparecerán aquí.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->recordUrl(null)
            ->recordAction(null)
            ->actions([
                Tables\Actions\Action::make('aprobar')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Inventario $record): bool => ! $record->aprobado && auth()->user()->can('aprobaciones.aprobar'))
                    ->action(function (Inventario $record) {
                        $record->update(['aprobado' => true, 'estado_registro' => 'Aprobado', 'aprobado_por' => auth()->id(), 'fecha_aprobacion' => now()]);
                        Notification::make()->title('Registro aprobado exitosamente')->success()->send();
                    })
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('rechazar')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Inventario $record): bool => ! $record->aprobado && auth()->user()->can('aprobaciones.rechazar'))
                    ->action(function (Inventario $record) {
                        $record->delete();
                        Notification::make()->title('Registro rechazado y eliminado')->success()->send();
                    })
                    ->requiresConfirmation(),
                Tables\Actions\ViewAction::make()->iconButton()->slideOver(),
                Tables\Actions\EditAction::make()->iconButton()->successNotificationTitle('Registro de inventario actualizado correctamente'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('aprobarMasivo')
                        ->label('Aprobar seleccionados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (): bool => auth()->user()->can('aprobaciones.aprobar'))
                        ->action(function (Collection $records) {
                            foreach ($records as $record) {
                                if (! $record->aprobado) {
                                    $record->update(['aprobado' => true, 'estado_registro' => 'Aprobado', 'aprobado_por' => auth()->id(), 'fecha_aprobacion' => now()]);
                                }
                            }
                            Notification::make()->title('Registros aprobados exitosamente')->success()->send();
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\DeleteBulkAction::make()->successNotificationTitle('Registro de inventario eliminado'),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Identificación del activo')
                    ->schema([
                        TextEntry::make('num_serie')->label('No. serie')
                            ->fontFamily('mono')
                            ->icon('heroicon-m-qr-code'),
                        TextEntry::make('material.nombre')->label('Material')
                            ->icon('heroicon-m-cube'),
                        TextEntry::make('estado')->label('Estado')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Disponible' => 'success',
                                'Asignado' => 'warning',
                                'Dañado' => 'danger',
                                'Devuelto a Proveedor' => 'info',
                                default => 'gray',
                            }),
                    ])->columns(3),
                Section::make('Ubicación y asignación')
                    ->schema([
                        TextEntry::make('ubicacion_fisica')->label('Ubicación')
                            ->icon('heroicon-m-building-office'),
                        TextEntry::make('tipo_propiedad')->label('Propiedad')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Propio' => 'primary',
                                'Rentado' => 'gray',
                                default => 'gray',
                            }),
                        TextEntry::make('usuario.name')->label('Registrado por')
                            ->icon('heroicon-m-user'),
                    ])->columns(3),
                Section::make('Detalles adicionales')
                    ->schema([
                        TextEntry::make('proveedor.nombre_empresa')->label('Proveedor'),
                        TextEntry::make('num_factura')->label('Factura'),
                        TextEntry::make('garantia_estado')->label('Garantía'),
                        TextEntry::make('observaciones_generales')->label('Observaciones')->columnSpanFull(),
                    ])->columns(3),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventarios::route('/'),
            'create' => Pages\CreateInventario::route('/create'),
            'edit' => Pages\EditInventario::route('/{record}/edit'),
        ];
    }
}
