<?php

namespace App\Filament\Resources;

use App\Enums\RoleEnum;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class UserResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = User::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Usuarios';

    protected static ?string $modelLabel = 'usuario';

    protected static ?string $pluralModelLabel = 'usuarios';

    protected static ?string $navigationGroup = 'Administración';

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
                    Forms\Components\Wizard\Step::make('Datos personales y acceso')
                        ->icon('heroicon-m-user-circle')
                    ->schema([
                        Forms\Components\TextInput::make('name')->label('Nombre')
                            ->label('Nombre(s)')
                            ->required()
                            ->prefixIcon('heroicon-m-user')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('apellido_paterno')
                            ->label('Apellido paterno')
                            ->prefixIcon('heroicon-m-user')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('apellido_materno')
                            ->label('Apellido materno')
                            ->prefixIcon('heroicon-m-user')
                            ->maxLength(255),
                        Forms\Components\Grid::make(2)
                            ->schema([
                        Forms\Components\TextInput::make('email')->label('Correo electrónico')
                            ->label('Correo electrónico')
                            ->email()
                            ->required()
                            ->prefixIcon('heroicon-m-envelope')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('password')->label('Contraseña')
                            ->label('Contraseña')
                            ->password()
                            ->prefixIcon('heroicon-m-key')
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->maxLength(255),
                    ])->columns(3),

                    Forms\Components\Wizard\Step::make('Datos institucionales y rol')
                        ->icon('heroicon-m-academic-cap')
                    ->schema([
                        Forms\Components\TextInput::make('num_control')
                            ->label('Número de control')
                            ->prefixIcon('heroicon-m-identification')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('carrera')
                            ->label('Carrera / área')
                            ->prefixIcon('heroicon-m-academic-cap')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('RFC')
                            ->label('Rfc (opcional)')
                            ->prefixIcon('heroicon-m-identification')
                            ->maxLength(13),
                    ])->columns(3),

                        Forms\Components\Grid::make(3)
                            ->schema([
                        Forms\Components\Select::make('tipo_usuario')->placeholder('Selecciona...')
                            ->label('Tipo de usuario (sistema)')
                            ->options([
                                RoleEnum::ADMIN->value => RoleEnum::ADMIN->value,
                                RoleEnum::SERVICIO_TIPO->value => RoleEnum::SERVICIO_SOCIAL->value,
                                'Pendiente' => 'Pendiente',
                            ])
                            ->default('Pendiente')
                            ->prefixIcon('heroicon-m-shield-check')
                            ->required(),
                        // NOTA: Se eliminó el campo 'roles' de Spatie por incompatibilidad con MongoDB
                        Forms\Components\ToggleButtons::make('activo')
                            ->label('Usuario activo')
                            ->boolean(trueLabel: 'Sí', falseLabel: 'No')
                            ->inline()
                            ->default(true)
                            ->required(),
                            ])->columns(3),
                    ])->columnSpanFull(),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->wrap(false)->limit(30)->grow(false)->label('Nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)->label('Correo electrónico')
                    ->label('Correo electrónico')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('num_control')->wrap(false)->limit(30)->grow(false)
                    ->label('No. control')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipo_usuario')->wrap(false)->limit(30)->grow(false)
                    ->label('Tipo')
                    ->badge()
                    ->colors([
                        'success' => RoleEnum::ADMIN->value,
                        'warning' => RoleEnum::SERVICIO_TIPO->value,
                        'gray' => 'Pendiente',
                    ])
                    ->icons([
                        'heroicon-m-shield-check' => RoleEnum::ADMIN->value,
                        'heroicon-m-academic-cap' => RoleEnum::SERVICIO_TIPO->value,
                        'heroicon-m-clock' => 'Pendiente',
                    ]),
                Tables\Columns\TextColumn::make('activo')->wrap(false)->limit(30)->grow(false)
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => $state ? 'Activo' : 'Inactivo')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->icon(fn ($state) => $state ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle'),
                Tables\Columns\TextColumn::make('created_at')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)->label('Fecha de creación')
                    ->label('Fecha de creación')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->defaultPaginationPageOption(10)
            ->filters([
                Tables\Filters\SelectFilter::make('tipo_usuario')
                    ->options([
                        RoleEnum::ADMIN->value => RoleEnum::ADMIN->value,
                        RoleEnum::SERVICIO_TIPO->value => RoleEnum::SERVICIO_SOCIAL->value,
                        'Pendiente' => 'Pendiente',
                    ]),
            ])
            ->recordUrl(null)
            ->recordAction(null)
            ->actions([
                Tables\Actions\ViewAction::make()->iconButton()->slideOver(),
                Tables\Actions\EditAction::make()->iconButton()->successNotificationTitle('Usuario actualizado correctamente'),
                Tables\Actions\DeleteAction::make()->requiresConfirmation()->successNotificationTitle('Usuario eliminado')
                    ->modalHeading('Eliminar usuario')
                    ->modalDescription('¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.')
                    ->modalSubmitActionLabel('Sí, eliminar')
                    ->iconButton()
                    ->hidden(fn (User $record): bool => $record->email === 'admin@tecnm.edu.mx'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function (Collection $records) {
                            $records->filter(fn ($record) => $record->email !== 'admin@tecnm.edu.mx')->each->delete();
                        }),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Datos personales')
                    ->schema([
                        TextEntry::make('name')->label('Nombre')->icon('heroicon-m-user'),
                        TextEntry::make('apellido_paterno')->label('Apellido paterno')->icon('heroicon-m-user'),
                        TextEntry::make('apellido_materno')->label('Apellido materno')->icon('heroicon-m-user'),
                    ])->columns(3),
                Section::make('Datos de acceso')
                    ->schema([
                        TextEntry::make('email')->label('Correo electrónico')->icon('heroicon-m-envelope'),
                    ])->columns(1),
                Section::make('Datos institucionales')
                    ->schema([
                        TextEntry::make('num_control')->label('Número de control')->icon('heroicon-m-identification'),
                        TextEntry::make('carrera')->label('Carrera')->icon('heroicon-m-academic-cap'),
                        TextEntry::make('RFC')->label('Rfc')->fontFamily('mono')->icon('heroicon-m-identification'),
                    ])->columns(3),
                Section::make('Estado y rol')
                    ->schema([
                        TextEntry::make('tipo_usuario')
                            ->label('Tipo de usuario')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                RoleEnum::ADMIN->value => 'success',
                                RoleEnum::SERVICIO_TIPO->value => 'warning',
                                default => 'gray',
                            })
                            ->icon(fn (string $state): string => match ($state) {
                                RoleEnum::ADMIN->value => 'heroicon-m-shield-check',
                                RoleEnum::SERVICIO_TIPO->value => 'heroicon-m-academic-cap',
                                default => 'heroicon-m-clock',
                            }),
                        // NOTA: Se eliminó roles.name
                        TextEntry::make('activo')
                            ->label('Estado')
                            ->formatStateUsing(fn ($state) => $state ? 'Activo' : 'Inactivo')
                            ->badge()
                            ->color(fn ($state) => $state ? 'success' : 'danger')
                            ->icon(fn ($state) => $state ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle'),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
