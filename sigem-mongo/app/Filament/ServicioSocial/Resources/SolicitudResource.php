<?php

namespace App\Filament\ServicioSocial\Resources;

use App\Filament\ServicioSocial\Resources\SolicitudResource\Pages;
use App\Models\Solicitud;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SolicitudResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Solicitud::class;

    protected static ?string $recordTitleAttribute = 'observaciones';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Solicitudes';

    protected static ?string $modelLabel = 'solicitud';

    protected static ?string $pluralModelLabel = 'solicitudes';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?int $navigationSort = 2;

    public static function hasRecordTitle(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Datos de la solicitud')
                        ->icon('heroicon-m-document-text')
                        ->schema([
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\DatePicker::make('fecha_solicitud')
                                        ->default(now())
                                        ->required()
                                        ->prefixIcon('heroicon-m-calendar')
                                        ->columnSpan(1),
                                    Forms\Components\ToggleButtons::make('tipo_movimiento')
                                        ->options([
                                            'Asignacion Temporal' => 'Asignación Temporal',
                                            'Asignacion Permanente' => 'Asignación Permanente',
                                        ])
                                        ->colors([
                                            'Asignacion Temporal' => 'warning',
                                            'Asignacion Permanente' => 'success',
                                        ])
                                        ->inline()
                                        ->default('Asignacion Temporal')
                                        ->required()
                                        ->live()
                                        ->columnSpan(2),
                                ]),
                            Forms\Components\ToggleButtons::make('estado')
                                ->options([
                                    'Pendiente' => 'Pendiente',
                                ])
                                ->colors([
                                    'Pendiente' => 'warning',
                                ])
                                ->inline()
                                ->default('Pendiente')
                                ->required()
                                ->columnSpanFull(),
                            Forms\Components\Textarea::make('observaciones')
                                ->label('Detalle / motivo de la solicitud')
                                ->placeholder('Explica qué necesitas y para qué...')
                                ->rows(3)
                                ->required()
                                ->columnSpanFull(),
                            Forms\Components\Repeater::make('detalles')
                                ->label('Materiales solicitados')
                                ->relationship('detalles')
                                ->schema([
                                    Forms\Components\Select::make('id_producto')
                                        ->label('Material')
                                        ->relationship('material', 'nombre')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->columnSpan(2),
                                    Forms\Components\TextInput::make('cantidad')
                                        ->label('Cantidad')
                                        ->numeric()
                                        ->default(1)
                                        ->required()
                                        ->columnSpan(1),
                                ])
                                ->columns(3)
                                ->columnSpanFull()
                                ->defaultItems(1)
                                ->addActionLabel('Añadir material'),
                        ]),

                    Forms\Components\Wizard\Step::make('Participantes y detalles')
                        ->icon('heroicon-m-users')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Select::make('id_usuario')
                                        ->label('Solicitante (usuario)')
                                        ->relationship('usuario', 'name')
                                        ->default(auth()->id())
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->prefixIcon('heroicon-m-user'),
                                    Forms\Components\Select::make('id_receptor')
                                        ->label('Receptor (área/persona)')
                                        ->relationship('receptor', 'nombre')
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->prefixIcon('heroicon-m-user-group')
                                        ->live(),
                                ]),
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\DatePicker::make('fecha_devolucion_estimada')
                                        ->label('Devolución estimada')
                                        ->prefixIcon('heroicon-m-calendar-days')
                                        ->visible(fn (Forms\Get $get) => $get('tipo_movimiento') === 'Asignacion Temporal'),
                                    Forms\Components\DatePicker::make('fecha_devolucion_real')
                                        ->label('Devolución real')
                                        ->prefixIcon('heroicon-m-calendar-days')
                                        ->visible(fn (Forms\Get $get) => $get('tipo_movimiento') === 'Asignacion Temporal'),
                                ]),
                        ]),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id_solicitud')->wrap(false)->limit(30)->grow(false)
                    ->label('Folio')
                    ->formatStateUsing(fn ($state) => 'FOLIO-'.str_pad($state, 5, '0', STR_PAD_LEFT))
                    ->fontFamily('mono')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tipo_movimiento')->wrap(false)->limit(30)->grow(false)
                    ->label('Tipo de movimiento')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('receptor.nombre')->wrap(false)->limit(30)->grow(false)
                    ->label('Receptor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('fecha_solicitud')->wrap(false)->limit(30)->grow(false)
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('estado')->wrap(false)->limit(30)->grow(false)
                    ->badge()
                    ->colors([
                        'warning' => 'Pendiente',
                        'primary' => 'Autorizado',
                        'success' => 'Completado',
                        'danger' => 'Rechazado',
                        'gray' => 'Cancelado',
                    ])
                    ->icons([
                        'heroicon-m-clock' => 'Pendiente',
                        'heroicon-m-check-circle' => 'Autorizado',
                        'heroicon-m-check-badge' => 'Completado',
                        'heroicon-m-x-circle' => 'Rechazado',
                        'heroicon-m-no-symbol' => 'Cancelado',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->defaultPaginationPageOption(10)
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'Pendiente' => 'Pendiente',
                        'Autorizado' => 'Autorizado',
                        'Rechazado' => 'Rechazado',
                        'Completado' => 'Completado',
                        'Cancelado' => 'Cancelado',
                    ]),
            ])
            ->recordUrl(null)
            ->recordAction(null)
            ->actions([
                Tables\Actions\ViewAction::make()->iconButton()->slideOver(),
                Tables\Actions\EditAction::make()->iconButton()->successNotificationTitle('Solicitud actualizada correctamente'),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Datos de la solicitud')
                    ->schema([
                        TextEntry::make('id_solicitud')->label('Folio')
                            ->formatStateUsing(fn ($state) => 'FOLIO-'.str_pad($state, 5, '0', STR_PAD_LEFT))
                            ->fontFamily('mono')
                            ->icon('heroicon-m-hashtag'),
                        TextEntry::make('fecha_solicitud')->label('Fecha')->date('d/m/Y')->icon('heroicon-m-calendar'),
                        TextEntry::make('tipo_movimiento')->label('Tipo')->badge(),
                        TextEntry::make('estado')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Pendiente' => 'warning',
                                'Autorizado' => 'primary',
                                'Completado' => 'success',
                                'Rechazado' => 'danger',
                                'Cancelado' => 'gray',
                                default => 'gray',
                            })
                            ->icon(fn (string $state): string => match ($state) {
                                'Pendiente' => 'heroicon-m-clock',
                                'Autorizado' => 'heroicon-m-check-circle',
                                'Completado' => 'heroicon-m-check-badge',
                                'Rechazado' => 'heroicon-m-x-circle',
                                'Cancelado' => 'heroicon-m-no-symbol',
                                default => 'heroicon-m-minus',
                            }),
                    ])->columns(4),
                Section::make('Participantes')
                    ->schema([
                        TextEntry::make('usuario.name')->label('Solicitante')->icon('heroicon-m-user'),
                        TextEntry::make('receptor.nombre')->label('Receptor')->icon('heroicon-m-user-group'),
                        TextEntry::make('receptor.area.departamento.nombre')->label('Departamento')->icon('heroicon-m-building-office'),
                    ])->columns(3),
                Section::make('Devoluciones y observaciones')
                    ->schema([
                        TextEntry::make('fecha_devolucion_estimada')->label('Devolución estimada')->date('d/m/Y')->icon('heroicon-m-calendar-days'),
                        TextEntry::make('fecha_devolucion_real')->label('Devolución real')->date('d/m/Y')->icon('heroicon-m-calendar-days'),
                        TextEntry::make('observaciones')->label('Observaciones')->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSolicituds::route('/'),
            'create' => Pages\CreateSolicitud::route('/create'),
            'edit' => Pages\EditSolicitud::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('id_usuario', auth()->id());
    }
}
