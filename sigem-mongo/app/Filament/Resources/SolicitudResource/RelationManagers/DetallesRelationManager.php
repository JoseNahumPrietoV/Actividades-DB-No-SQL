<?php

namespace App\Filament\Resources\SolicitudResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DetallesRelationManager extends RelationManager
{
    protected static string $relationship = 'detalles';

    protected static ?string $title = 'Detalles de la Solicitud';

    protected static ?string $modelLabel = 'detalle';

    protected static ?string $pluralModelLabel = 'detalles';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('id_producto')
                    ->label('Material / equipo')
                    ->relationship('material', 'nombre')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('cantidad')
                    ->label('Cantidad')
                    ->numeric()
                    ->default(1)
                    ->required()
                    ->minValue(1),
                Forms\Components\Select::make('id_inventario')
                    ->label('Activo (inventario)')
                    ->relationship('inventario', 'num_serie')
                    ->searchable()
                    ->preload(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id_detalle')
            ->columns([
                Tables\Columns\TextColumn::make('material.nombre')
                    ->label('Material / equipo'),
                Tables\Columns\TextColumn::make('cantidad')
                    ->label('Cantidad')
                    ->numeric(),
                Tables\Columns\TextColumn::make('inventario.num_serie')
                    ->label('No. serie (asignado)')
                    ->fontFamily('mono')
                    ->placeholder('N/A'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->recordUrl(null)
            ->recordAction(null)
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
