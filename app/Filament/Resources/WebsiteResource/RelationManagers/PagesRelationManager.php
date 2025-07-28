<?php

namespace App\Filament\Resources\WebsiteResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PagesRelationManager extends RelationManager
{
    protected static string $relationship = 'pages';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Page Information')
                    ->schema([
                        Forms\Components\TextInput::make('url')
                            ->url()
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('title')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->rows(3),
                        Forms\Components\TextInput::make('type')
                            ->maxLength(255)
                            ->helperText('e.g., page, product, collection, blog'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Analysis Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_analyzed')
                            ->label('Is Analyzed'),
                        Forms\Components\DateTimePicker::make('analyzed_at')
                            ->label('Analyzed At'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Lighthouse Data')
                    ->schema([
                        Forms\Components\KeyValue::make('lighthouse')
                            ->keyLabel('Lighthouse Metric')
                            ->valueLabel('Score')
                            ->helperText('Lighthouse performance scores'),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('SEO Data')
                    ->schema([
                        Forms\Components\KeyValue::make('seo')
                            ->keyLabel('SEO Metric')
                            ->valueLabel('Value')
                            ->helperText('SEO analysis results'),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Meta Data')
                    ->schema([
                        Forms\Components\KeyValue::make('meta_data')
                            ->keyLabel('Meta Attribute')
                            ->valueLabel('Value')
                            ->helperText('Additional metadata'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('url')
            ->columns([
                Tables\Columns\TextColumn::make('url')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(40)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'page' => 'gray',
                        'product' => 'success',
                        'collection' => 'info',
                        'blog' => 'warning',
                        default => 'primary',
                    })
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_analyzed')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lighthouse_score')
                    ->label('Performance')
                    ->getStateUsing(fn ($record) => $record->lighthouse['performance'] ?? null)
                    ->formatStateUsing(fn ($state) => $state ? $state . '%' : 'N/A')
                    ->color(fn ($state) => match (true) {
                        $state >= 90 => 'success',
                        $state >= 50 => 'warning',
                        $state > 0 => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('seo_score')
                    ->label('SEO')
                    ->getStateUsing(fn ($record) => $record->lighthouse['seo'] ?? null)
                    ->formatStateUsing(fn ($state) => $state ? $state . '%' : 'N/A')
                    ->color(fn ($state) => match (true) {
                        $state >= 90 => 'success',
                        $state >= 50 => 'warning',
                        $state > 0 => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('analyzed_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_analyzed')
                    ->label('Analysis Status')
                    ->placeholder('All pages')
                    ->trueLabel('Analyzed')
                    ->falseLabel('Not analyzed'),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'page' => 'Page',
                        'product' => 'Product',
                        'collection' => 'Collection',
                        'blog' => 'Blog',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
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