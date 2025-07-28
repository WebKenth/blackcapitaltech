<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WebsiteResource\Pages;
use App\Filament\Resources\WebsiteResource\RelationManagers;
use App\Models\Website;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WebsiteResource extends Resource
{
    protected static ?string $model = Website::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Website Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('url')
                            ->url()
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('slug')
                            ->maxLength(255)
                            ->helperText('Leave empty to auto-generate from URL'),
                        Forms\Components\TextInput::make('goal')
                            ->maxLength(255)
                            ->helperText('Purpose or goal of the website'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_processed')
                            ->label('Is Processed')
                            ->helperText('Whether the website has been analyzed'),
                        Forms\Components\Select::make('analysis_status')
                            ->options([
                                'pending' => 'Pending',
                                'queued' => 'Queued',
                                'processing' => 'Processing',
                                'completed' => 'Completed',
                                'failed' => 'Failed',
                            ])
                            ->default('pending'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Tags')
                    ->schema([
                        Forms\Components\TagsInput::make('tags')
                            ->helperText('Add tags to categorize this website'),
                    ]),

                Forms\Components\Section::make('SEO Data')
                    ->schema([
                        Forms\Components\KeyValue::make('seo')
                            ->keyLabel('SEO Metric')
                            ->valueLabel('Value')
                            ->helperText('SEO analysis results'),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Lighthouse Data')
                    ->schema([
                        Forms\Components\KeyValue::make('lighthouse')
                            ->keyLabel('Lighthouse Metric')
                            ->valueLabel('Score')
                            ->helperText('Lighthouse performance scores'),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Company Information')
                    ->schema([
                        Forms\Components\KeyValue::make('company')
                            ->keyLabel('Company Attribute')
                            ->valueLabel('Value')
                            ->helperText('Company information and details'),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Sitemap Data')
                    ->schema([
                        Forms\Components\Textarea::make('sitemap_data')
                            ->rows(5)
                            ->helperText('Raw sitemap data (JSON format)')
                            ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state)
                            ->dehydrateStateUsing(fn ($state) => json_decode($state, true)),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('url')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_processed')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('analysis_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'queued' => 'warning',
                        'processing' => 'info',
                        'completed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('pages_count')
                    ->counts('pages')
                    ->label('Pages')
                    ->sortable(),
                Tables\Columns\TextColumn::make('goal')
                    ->limit(30)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_processed')
                    ->label('Processing Status')
                    ->placeholder('All websites')
                    ->trueLabel('Processed')
                    ->falseLabel('Unprocessed'),
                Tables\Filters\SelectFilter::make('analysis_status')
                    ->options([
                        'pending' => 'Pending',
                        'queued' => 'Queued',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('analyze')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('info')
                    ->action(fn (Website $record) => $record->runAnalysis())
                    ->requiresConfirmation()
                    ->modalHeading('Run Website Analysis')
                    ->modalDescription('This will queue the website for analysis. Are you sure?'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWebsites::route('/'),
            'create' => Pages\CreateWebsite::route('/create'),
            'view' => Pages\ViewWebsite::route('/{record}'),
            'edit' => Pages\EditWebsite::route('/{record}/edit'),
        ];
    }
}