<?php

namespace App\Filament\ServicioSocial\Resources;

use App\Filament\ServicioSocial\Resources\MaterialResource\Pages;
use App\Models\Material;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class MaterialResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Material::class;

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationLabel = 'Materiales';

    protected static ?string $modelLabel = 'material';

    protected static ?string $pluralModelLabel = 'materiales';

    protected static ?string $navigationGroup = 'Catálogos';

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
                    Forms\Components\Wizard\Step::make('Datos y clasificación')
                        ->icon('heroicon-m-cube')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('nombre')
                                        ->required()
                                        ->prefixIcon('heroicon-m-tag')
                                        ->maxLength(150),
                                    Forms\Components\TextInput::make('modelo')
                                        ->prefixIcon('heroicon-m-qr-code')
                                        ->maxLength(100),
                                    Forms\Components\Textarea::make('descripcion')
                                        ->label('Descripción')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ]),
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\Select::make('id_marca')
                                        ->label('Marca')
                                        ->relationship('marca', 'nombre')
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->prefixIcon('heroicon-m-star'),
                                    Forms\Components\Select::make('id_tipodematerial')
                                        ->label('Tipo de material')
                                        ->relationship('tipo', 'nombre')
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->prefixIcon('heroicon-m-square-3-stack-3d'),
                                    Forms\Components\Select::make('id_unidad')
                                        ->label('Unidad de medida')
                                        ->relationship('unidad', 'nombre')
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->prefixIcon('heroicon-m-scale'),
                                ]),
                        ]),

                    Forms\Components\Wizard\Step::make('Control de stock')
                        ->icon('heroicon-m-chart-bar')
                        ->schema([
                            Forms\Components\TextInput::make('stock_actual')
                                ->label('Stock actual')
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->prefixIcon('heroicon-m-archive-box')
                                ->dehydrated()
                                ->required(),
                            Forms\Components\Hidden::make('stock_minimo')
                                ->default(0),
                            Forms\Components\Hidden::make('requiere_control_individual')
                                ->default(true),
                        ]),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->wrap(false)->limit(30)->grow(false)
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('modelo')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)
                    ->label('Modelo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('marca.nombre')->wrap(false)->limit(30)->grow(false)
                    ->label('Marca')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tipo.nombre')->wrap(false)->limit(30)->grow(false)
                    ->label('Tipo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_actual')->wrap(false)->limit(30)->grow(false)
                    ->label('Stock actual')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_minimo')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)
                    ->label('Stock mín.')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_status')->wrap(false)->limit(30)->grow(false)
                    ->label('Estado de stock')
                    ->getStateUsing(fn ($record) => $record->stock_actual < $record->stock_minimo ? 'Stock bajo' : 'Normal')
                    ->badge()
                    ->colors([
                        'danger' => 'Stock bajo',
                        'success' => 'Normal',
                    ])
                    ->icons([
                        'heroicon-m-exclamation-triangle' => 'Stock bajo',
                        'heroicon-m-check-circle' => 'Normal',
                    ]),
            ])
            ->defaultSort('nombre', 'asc')
            ->striped()
            ->defaultPaginationPageOption(10)
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordUrl(null)
            ->recordAction(null)
            ->actions([
                Tables\Actions\ViewAction::make()->iconButton()->slideOver(),
                Tables\Actions\EditAction::make()->iconButton(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Datos del material')
                    ->schema([
                        TextEntry::make('nombre')->label('Nombre')->icon('heroicon-m-tag'),
                        TextEntry::make('modelo')->label('Modelo')->icon('heroicon-m-qr-code'),
                        TextEntry::make('descripcion')->label('Descripción')->columnSpanFull(),
                    ])->columns(2),
                Section::make('Clasificación')
                    ->schema([
                        TextEntry::make('marca.nombre')->label('Marca')->icon('heroicon-m-star'),
                        TextEntry::make('tipo.nombre')->label('Tipo')->icon('heroicon-m-square-3-stack-3d'),
                        TextEntry::make('unidad.nombre')->label('Unidad de medida')->icon('heroicon-m-scale'),
                    ])->columns(3),
                Section::make('Control de stock')
                    ->schema([
                        TextEntry::make('stock_actual')->label('Stock actual')->icon('heroicon-m-archive-box'),
                        TextEntry::make('stock_minimo')->label('Stock mínimo')->icon('heroicon-m-bell-alert'),
                        TextEntry::make('estado_stock')
                            ->label('Estado')
                            ->getStateUsing(fn ($record) => $record->stock_actual < $record->stock_minimo ? 'Stock bajo' : 'Normal')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Stock bajo' => 'danger',
                                'Normal' => 'success',
                                default => 'gray',
                            })
                            ->icon(fn (string $state): string => match ($state) {
                                'Stock bajo' => 'heroicon-m-exclamation-triangle',
                                'Normal' => 'heroicon-m-check-circle',
                                default => 'heroicon-m-minus',
                            }),
                        IconEntry::make('requiere_control_individual')
                            ->label('Control individual')
                            ->boolean(),
                    ])->columns(4),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaterials::route('/'),
            'create' => Pages\CreateMaterial::route('/create'),
            'edit' => Pages\EditMaterial::route('/{record}/edit'),
        ];
    }
}
