<?php

namespace App\Filament\ServicioSocial\Resources;

use App\Enums\RoleEnum;
use App\Filament\ServicioSocial\Resources\MantenimientoResource\Pages;
use App\Models\Mantenimiento;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MantenimientoResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Mantenimiento::class;

    protected static ?string $recordTitleAttribute = 'descripcion_falla';

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Mantenimiento';

    protected static ?string $modelLabel = 'mantenimiento';

    protected static ?string $pluralModelLabel = 'mantenimientos';

    protected static ?string $navigationGroup = 'Inventario';

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
                    Forms\Components\Wizard\Step::make('Equipo y técnico')
                        ->icon('heroicon-m-wrench')
                        ->schema([
                            Forms\Components\Select::make('id_inventario')
                                ->label('Equipo a reparar')
                                ->relationship(
                                    name: 'inventario',
                                    titleAttribute: 'num_serie',
                                    modifyQueryUsing: fn ($query) => $query->with('material')
                                )
                                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->material?->nombre} - N/S: {$record->num_serie}")
                                ->required()
                                ->searchable(['num_serie', 'material.nombre'])
                                ->preload()
                                ->prefixIcon('heroicon-m-qr-code')
                                ->columnSpanFull(),
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('nombre_tecnico')
                                        ->label('Técnico / alumno')
                                        ->required()
                                        ->default(fn () => auth()->user() ? trim(auth()->user()->name.' '.auth()->user()->apellido_paterno.' '.auth()->user()->apellido_materno) : null)
                                        ->prefixIcon('heroicon-m-user')
                                        ->maxLength(150),
                                    Forms\Components\TextInput::make('num_control_tecnico')
                                        ->label('Número de control técnico')
                                        ->default(fn () => auth()->user()?->num_control)
                                        ->prefixIcon('heroicon-m-identification')
                                        ->maxLength(100),
                                ]),
                            Forms\Components\Hidden::make('id_usuario_solicita')
                                ->default(fn () => auth()->id()),
                        ]),

                    Forms\Components\Wizard\Step::make('Servicio y detalles')
                        ->icon('heroicon-m-cog')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\ToggleButtons::make('tipo_servicio')
                                        ->label('Tipo de servicio')
                                        ->options([
                                            RoleEnum::SERVICIO_SOCIAL->value => RoleEnum::SERVICIO_SOCIAL->value,
                                            'Prácticas Profesionales' => 'Prácticas Profesionales',
                                            'Personal Técnico' => 'Personal Técnico',
                                        ])
                                        ->colors([
                                            RoleEnum::SERVICIO_SOCIAL->value => 'primary',
                                            'Prácticas Profesionales' => 'info',
                                            'Personal Técnico' => 'success',
                                        ])
                                        ->inline()
                                        ->required(),
                                    Forms\Components\ToggleButtons::make('tipo_mantenimiento')
                                        ->options([
                                            'Preventivo' => 'Preventivo',
                                            'Correctivo' => 'Correctivo',
                                            'Mejora' => 'Mejora',
                                        ])
                                        ->colors([
                                            'Preventivo' => 'success',
                                            'Correctivo' => 'danger',
                                            'Mejora' => 'info',
                                        ])
                                        ->inline()
                                        ->required(),
                                ]),
                            Forms\Components\ToggleButtons::make('estado')
                                ->options([
                                    'Solicitado' => 'Solicitado',
                                    'En proceso' => 'En proceso',
                                    'Pendiente Revision Admin' => 'En revisión',
                                ])
                                ->colors([
                                    'Solicitado' => 'warning',
                                    'En proceso' => 'primary',
                                    'Pendiente Revision Admin' => 'warning',
                                ])
                                ->inline()
                                ->default('Pendiente Revision Admin')
                                ->required()
                                ->columnSpanFull(),
                            Forms\Components\DatePicker::make('fecha_solicitud')
                                ->default(now())
                                ->required()
                                ->prefixIcon('heroicon-m-calendar'),
                            Forms\Components\Textarea::make('descripcion_falla')
                                ->label('Descripción de la falla')
                                ->rows(3)
                                ->columnSpanFull(),
                            Forms\Components\Textarea::make('observaciones')
                                ->label('Observaciones')
                                ->rows(3)
                                ->columnSpanFull(),
                        ]),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('inventario.num_serie')->wrap(false)->limit(30)->grow(false)
                    ->label('No. serie (activo)')
                    ->fontFamily('mono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('inventario.material.nombre')->wrap(false)->limit(30)->grow(false)
                    ->label('Material')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipo_mantenimiento')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)
                    ->label('Tipo mantenimiento')
                    ->badge()
                    ->colors([
                        'success' => 'Preventivo',
                        'danger' => 'Correctivo',
                        'primary' => 'Mejora',
                    ])
                    ->icons([
                        'heroicon-m-shield-check' => 'Preventivo',
                        'heroicon-m-exclamation-triangle' => 'Correctivo',
                        'heroicon-m-arrow-trending-up' => 'Mejora',
                    ])
                    ->searchable(),
                Tables\Columns\TextColumn::make('nombre_tecnico')->wrap(false)->limit(30)->grow(false)
                    ->label('Técnico')
                    ->searchable(),
                Tables\Columns\TextColumn::make('estado')->wrap(false)->limit(30)->grow(false)
                    ->formatStateUsing(fn ($state) => $state === 'Pendiente Revision Admin' ? 'En revisión' : $state)
                    ->badge()
                    ->colors([
                        'warning' => 'Solicitado',
                        'primary' => 'En proceso',
                        'info' => 'Pendiente Revision Admin',
                        'success' => 'Completado',
                        'danger' => 'Cancelado',
                    ])
                    ->icons([
                        'heroicon-m-clock' => ['Solicitado', 'Pendiente Revision Admin'],
                        'heroicon-m-wrench-screwdriver' => 'En proceso',
                        'heroicon-m-check-badge' => 'Completado',
                        'heroicon-m-x-circle' => 'Cancelado',
                    ]),
                Tables\Columns\TextColumn::make('fecha_solicitud')->wrap(false)->limit(30)->grow(false)
                    ->label('Fecha sol.')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->defaultPaginationPageOption(10)
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'Solicitado' => 'Solicitado',
                        'En proceso' => 'En proceso',
                        'Pendiente Revision Admin' => 'En revisión',
                    ]),
            ])
            ->recordUrl(null)
            ->recordAction(null)
            ->actions([
                Tables\Actions\ViewAction::make()->iconButton()->slideOver(),
                Tables\Actions\EditAction::make()->iconButton()->successNotificationTitle('Mantenimiento actualizado correctamente'),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Equipo y técnico')
                    ->schema([
                        TextEntry::make('inventario.num_serie')->label('No. serie')
                            ->fontFamily('mono')
                            ->icon('heroicon-m-qr-code'),
                        TextEntry::make('inventario.material.nombre')->label('Material')->icon('heroicon-m-cube'),
                        TextEntry::make('nombre_tecnico')->label('Técnico')->icon('heroicon-m-user'),
                        TextEntry::make('num_control_tecnico')->label('No. control')->icon('heroicon-m-identification'),
                    ])->columns(4),
                Section::make('Detalles del servicio')
                    ->schema([
                        TextEntry::make('tipo_servicio')->label('Servicio')->badge(),
                        TextEntry::make('tipo_mantenimiento')->label('Tipo')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Preventivo' => 'success',
                                'Correctivo' => 'danger',
                                'Mejora' => 'info',
                                default => 'gray',
                            })
                            ->icon(fn (string $state): string => match ($state) {
                                'Preventivo' => 'heroicon-m-shield-check',
                                'Correctivo' => 'heroicon-m-exclamation-triangle',
                                'Mejora' => 'heroicon-m-arrow-trending-up',
                                default => 'heroicon-m-minus',
                            }),
                        TextEntry::make('estado')->label('Estado')
                            ->formatStateUsing(fn ($state) => $state === 'Pendiente Revision Admin' ? 'En revisión' : $state)
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Solicitado' => 'warning',
                                'En proceso' => 'primary',
                                'Pendiente Revision Admin' => 'info',
                                'Completado' => 'success',
                                'Cancelado' => 'danger',
                                default => 'gray',
                            })
                            ->icon(fn (string $state): string => match ($state) {
                                'Solicitado' => 'heroicon-m-clock',
                                'En proceso' => 'heroicon-m-wrench-screwdriver',
                                'Pendiente Revision Admin' => 'heroicon-m-clock',
                                'Completado' => 'heroicon-m-check-badge',
                                'Cancelado' => 'heroicon-m-x-circle',
                                default => 'heroicon-m-minus',
                            }),
                    ])->columns(3),
                Section::make('Fechas y reportes')
                    ->schema([
                        TextEntry::make('fecha_solicitud')->label('Solicitud')->date('d/m/Y')->icon('heroicon-m-calendar'),
                        TextEntry::make('descripcion_falla')->label('Falla')->columnSpanFull(),
                        TextEntry::make('observaciones')->label('Observaciones')->columnSpanFull(),
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
            'index' => Pages\ListMantenimientos::route('/'),
            'create' => Pages\CreateMantenimiento::route('/create'),
            'edit' => Pages\EditMantenimiento::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('id_usuario_solicita', auth()->id());
    }
}
