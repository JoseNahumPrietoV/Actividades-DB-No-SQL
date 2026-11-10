<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProveedorResource\Pages;
use App\Models\Proveedor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ProveedorResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Proveedor::class;

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Proveedores';

    protected static ?string $modelLabel = 'proveedor';

    protected static ?string $pluralModelLabel = 'proveedores';

    protected static ?string $navigationGroup = 'Catálogos';

    protected static ?int $navigationSort = 6;

    public static function hasRecordTitle(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos de la empresa')
                    ->icon('heroicon-m-building-office-2')
                    ->schema([
                        Forms\Components\TextInput::make('nombre_empresa')
                            ->label('Nombre de la empresa')
                            ->required()
                            ->prefixIcon('heroicon-m-building-storefront')
                            ->maxLength(150),
                        Forms\Components\TextInput::make('rfc')
                            ->label('Rfc')
                            ->prefixIcon('heroicon-m-identification')
                            ->maxLength(13)
                            ->default(null),
                        Forms\Components\ToggleButtons::make('activo')
                            ->label('Proveedor activo')
                            ->boolean(trueLabel: 'Sí', falseLabel: 'No')
                            ->inline()
                            ->default(true)
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('Información de contacto')
                    ->icon('heroicon-m-phone')
                    ->schema([
                        Forms\Components\TextInput::make('contacto_nombre')
                            ->label('Nombre del contacto')
                            ->prefixIcon('heroicon-m-user')
                            ->maxLength(100)
                            ->default(null),
                        Forms\Components\TextInput::make('contacto_telefono')
                            ->label('Teléfono')
                            ->tel()
                            ->prefixIcon('heroicon-m-device-phone-mobile')
                            ->maxLength(20)
                            ->default(null),
                        Forms\Components\TextInput::make('contacto_email')
                            ->label('Correo electrónico')
                            ->email()
                            ->prefixIcon('heroicon-m-envelope')
                            ->maxLength(100)
                            ->default(null),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre_empresa')->wrap(false)->limit(30)->grow(false)
                    ->label('Empresa')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rfc')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)
                    ->label('Rfc')
                    ->fontFamily('mono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('contacto_nombre')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)
                    ->label('Contacto')
                    ->searchable(),
                Tables\Columns\TextColumn::make('contacto_telefono')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)
                    ->label('Teléfono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('contacto_email')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('activo')->wrap(false)->limit(30)->grow(false)
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => $state ? 'Activo' : 'Inactivo')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->icon(fn ($state) => $state ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle'),
                Tables\Columns\TextColumn::make('created_at')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)->label('Fecha de creación')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('nombre_empresa', 'asc')
            ->striped()
            ->defaultPaginationPageOption(10)
            ->filters([
                TrashedFilter::make(),
                //
            ])
            ->recordUrl(null)
            ->recordAction(null)
            ->actions([
                Tables\Actions\ViewAction::make()->iconButton()->slideOver(),
                Tables\Actions\EditAction::make()->iconButton()->successNotificationTitle('Proveedor actualizado correctamente'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->successNotificationTitle('Proveedor eliminado'),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Datos de la empresa')
                    ->schema([
                        TextEntry::make('nombre_empresa')->label('Empresa')->icon('heroicon-m-building-storefront'),
                        TextEntry::make('rfc')->label('Rfc')->fontFamily('mono')->icon('heroicon-m-identification'),
                        TextEntry::make('activo')
                            ->label('Estado')
                            ->formatStateUsing(fn ($state) => $state ? 'Activo' : 'Inactivo')
                            ->badge()
                            ->color(fn ($state) => $state ? 'success' : 'danger')
                            ->icon(fn ($state) => $state ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle'),
                    ])->columns(3),
                Section::make('Contacto')
                    ->schema([
                        TextEntry::make('contacto_nombre')->label('Contacto')->icon('heroicon-m-user'),
                        TextEntry::make('contacto_telefono')->label('Teléfono')->icon('heroicon-m-device-phone-mobile'),
                        TextEntry::make('contacto_email')->label('Email')->icon('heroicon-m-envelope'),
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
            'index' => Pages\ListProveedors::route('/'),
            'create' => Pages\CreateProveedor::route('/create'),
            'edit' => Pages\EditProveedor::route('/{record}/edit'),
        ];
    }
}
