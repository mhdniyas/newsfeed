<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var array<int, array{name: string, slug: string, description: string, topics: array<int, array<int, string>>}>
     */
    protected array $extraSections = [
        ['name' => 'United States', 'slug' => 'united-states', 'description' => 'US news, policy, culture, and national developments.', 'topics' => [['US Headlines', 'united states news'], ['Washington Watch', 'washington news'], ['US Economy', 'us economy news']]],
        ['name' => 'Europe', 'slug' => 'europe', 'description' => 'Europe-wide news, diplomacy, and regional affairs.', 'topics' => [['Europe Headlines', 'europe news'], ['EU Watch', 'eu news'], ['Europe Markets', 'europe economy news']]],
        ['name' => 'Middle East', 'slug' => 'middle-east', 'description' => 'Regional updates across the Middle East.', 'topics' => [['Middle East Headlines', 'middle east news'], ['Gulf Updates', 'gulf news'], ['Regional Diplomacy', 'middle east diplomacy news']]],
        ['name' => 'Africa', 'slug' => 'africa', 'description' => 'African politics, economy, sport, and society.', 'topics' => [['Africa Headlines', 'africa news'], ['Africa Business', 'africa business news'], ['Africa Sport', 'africa sports news']]],
        ['name' => 'Asia', 'slug' => 'asia', 'description' => 'Asia-wide politics, business, and technology coverage.', 'topics' => [['Asia Headlines', 'asia news'], ['Asia Markets', 'asia market news'], ['Asia Tech', 'asia tech news']]],
        ['name' => 'India', 'slug' => 'india', 'description' => 'India-focused politics, economy, and national headlines.', 'topics' => [['India Headlines', 'india news'], ['India Politics', 'india politics news'], ['India Markets', 'india market news']]],
        ['name' => 'China', 'slug' => 'china', 'description' => 'China business, policy, and international developments.', 'topics' => [['China Headlines', 'china news'], ['China Economy', 'china economy news'], ['China Tech', 'china tech news']]],
        ['name' => 'Japan', 'slug' => 'japan', 'description' => 'Japan policy, business, innovation, and culture coverage.', 'topics' => [['Japan Headlines', 'japan news'], ['Japan Business', 'japan business news'], ['Japan Technology', 'japan technology news']]],
        ['name' => 'South America', 'slug' => 'south-america', 'description' => 'South American politics, football, and economy.', 'topics' => [['South America Headlines', 'south america news'], ['Latam Markets', 'latin america market news'], ['Latam Football', 'south america football news']]],
        ['name' => 'North America', 'slug' => 'north-america', 'description' => 'North American regional coverage beyond the US.', 'topics' => [['North America Headlines', 'north america news'], ['Canada Watch', 'canada news'], ['Mexico Watch', 'mexico news']]],
        ['name' => 'Breaking News', 'slug' => 'breaking-news', 'description' => 'Fast-moving breaking stories across major topics.', 'topics' => [['Breaking News Radar', 'breaking news'], ['Urgent Headlines', 'latest breaking headlines'], ['Global Alerts', 'world breaking news']]],
        ['name' => 'Editor Picks', 'slug' => 'editor-picks', 'description' => 'High-interest stories that deserve wider placement.', 'topics' => [['Editor Picks', 'editor picks news'], ['Must Read Stories', 'must read news'], ['Top Stories Radar', 'top stories today']]],
        ['name' => 'Markets', 'slug' => 'markets', 'description' => 'Stocks, bonds, commodities, and macro market action.', 'topics' => [['Stock Market', 'stock market news'], ['Commodities', 'commodities news'], ['Macro Markets', 'global market news']]],
        ['name' => 'Startups', 'slug' => 'startups', 'description' => 'Startup launches, funding rounds, and founder updates.', 'topics' => [['Startup Headlines', 'startup news'], ['Funding Roundup', 'startup funding news'], ['Founder Watch', 'founder news']]],
        ['name' => 'Cybersecurity', 'slug' => 'cybersecurity', 'description' => 'Security incidents, privacy, and cyber defense.', 'topics' => [['Cybersecurity Headlines', 'cybersecurity news'], ['Data Breaches', 'data breach news'], ['Security Research', 'security research news']]],
        ['name' => 'Cloud', 'slug' => 'cloud', 'description' => 'Cloud computing, infrastructure, and enterprise platforms.', 'topics' => [['Cloud Headlines', 'cloud computing news'], ['Enterprise Platforms', 'enterprise cloud news'], ['Data Centers', 'data center news']]],
        ['name' => 'Mobile Tech', 'slug' => 'mobile-tech', 'description' => 'Phones, mobile apps, and device platform updates.', 'topics' => [['Mobile Tech Headlines', 'mobile tech news'], ['Android Watch', 'android news'], ['iPhone Watch', 'iphone news']]],
        ['name' => 'Gaming', 'slug' => 'gaming', 'description' => 'Console, PC, esports, and mobile gaming stories.', 'topics' => [['Gaming Headlines', 'gaming news'], ['Esports Watch', 'esports news'], ['Mobile Gaming', 'mobile gaming news']]],
        ['name' => 'Esports', 'slug' => 'esports', 'description' => 'Competitive gaming tournaments, teams, and players.', 'topics' => [['Esports Headlines', 'esports news'], ['Tournament Results', 'esports tournament news'], ['Team Moves', 'esports roster news']]],
        ['name' => 'Football Clubs', 'slug' => 'football-clubs', 'description' => 'Club football transfer and match-day coverage.', 'topics' => [['Club Football', 'club football news'], ['Transfer Watch', 'football transfer news'], ['Premier League Radar', 'premier league news']]],
        ['name' => 'Transfer News', 'slug' => 'transfer-news', 'description' => 'Transfer windows, contract talks, and player moves.', 'topics' => [['Transfer Headlines', 'transfer news'], ['Contract Talks', 'football contract news'], ['Rumour Watch', 'transfer rumours']]],
        ['name' => 'Tennis', 'slug' => 'tennis', 'description' => 'Grand Slams, ATP, WTA, and player coverage.', 'topics' => [['Tennis Headlines', 'tennis news'], ['ATP Tour', 'atp news'], ['WTA Tour', 'wta news']]],
        ['name' => 'Formula 1', 'slug' => 'formula-1', 'description' => 'F1 race weekends, teams, and driver stories.', 'topics' => [['F1 Headlines', 'formula 1 news'], ['Driver Market', 'f1 driver news'], ['Race Weekend', 'f1 race news']]],
        ['name' => 'Olympics', 'slug' => 'olympics', 'description' => 'Olympic sports, athletes, and governing body news.', 'topics' => [['Olympics Headlines', 'olympics news'], ['Athlete Stories', 'olympic athlete news'], ['Olympic Committee', 'olympic committee news']]],
        ['name' => 'Travel', 'slug' => 'travel', 'description' => 'Airlines, tourism, visas, and destination trends.', 'topics' => [['Travel Headlines', 'travel news'], ['Airline Watch', 'airline news'], ['Tourism Trends', 'tourism news']]],
        ['name' => 'Climate', 'slug' => 'climate', 'description' => 'Climate policy, science, and extreme weather.', 'topics' => [['Climate Headlines', 'climate news'], ['Extreme Weather', 'extreme weather news'], ['Climate Policy', 'climate policy news']]],
        ['name' => 'Space', 'slug' => 'space', 'description' => 'Launches, space agencies, and astronomy discoveries.', 'topics' => [['Space Headlines', 'space news'], ['NASA Watch', 'nasa news'], ['Rocket Launches', 'rocket launch news']]],
        ['name' => 'Education', 'slug' => 'education', 'description' => 'Schools, universities, policy, and student trends.', 'topics' => [['Education Headlines', 'education news'], ['University Watch', 'university news'], ['School Policy', 'school policy news']]],
        ['name' => 'Law & Justice', 'slug' => 'law-justice', 'description' => 'Courts, legal policy, and justice system coverage.', 'topics' => [['Law Headlines', 'legal news'], ['Court Watch', 'court news'], ['Justice Updates', 'justice news']]],
        ['name' => 'Real Estate', 'slug' => 'real-estate', 'description' => 'Housing, commercial property, and real-estate trends.', 'topics' => [['Real Estate Headlines', 'real estate news'], ['Housing Market', 'housing market news'], ['Commercial Property', 'commercial real estate news']]],
        ['name' => 'Energy', 'slug' => 'energy', 'description' => 'Oil, gas, renewables, and energy policy.', 'topics' => [['Energy Headlines', 'energy news'], ['Oil Market', 'oil price news'], ['Renewables Watch', 'renewable energy news']]],
        ['name' => 'Jobs', 'slug' => 'jobs', 'description' => 'Employment trends, hiring, and labour market shifts.', 'topics' => [['Jobs Headlines', 'jobs news'], ['Labour Market', 'labor market news'], ['Hiring Trends', 'hiring news']]],
        ['name' => 'Retail', 'slug' => 'retail', 'description' => 'Retail chains, e-commerce, and consumer demand.', 'topics' => [['Retail Headlines', 'retail news'], ['E-commerce Watch', 'ecommerce news'], ['Consumer Spending', 'consumer spending news']]],
        ['name' => 'Luxury', 'slug' => 'luxury', 'description' => 'Luxury brands, watches, fashion, and premium retail.', 'topics' => [['Luxury Headlines', 'luxury market news'], ['Fashion Houses', 'fashion industry news'], ['Watch Market', 'watch industry news']]],
        ['name' => 'Automotive', 'slug' => 'automotive', 'description' => 'Car makers, EVs, recalls, and mobility trends.', 'topics' => [['Automotive Headlines', 'automotive news'], ['EV Watch', 'electric vehicle news'], ['Car Industry', 'car industry news']]],
        ['name' => 'EV', 'slug' => 'ev', 'description' => 'Electric vehicles, batteries, and charging infrastructure.', 'topics' => [['EV Headlines', 'ev news'], ['Battery Supply', 'battery industry news'], ['Charging Network', 'ev charging news']]],
        ['name' => 'Social Media', 'slug' => 'social-media', 'description' => 'Platforms, creator economy, and moderation updates.', 'topics' => [['Social Media Headlines', 'social media news'], ['Creator Economy', 'creator economy news'], ['Platform Policy', 'platform policy news']]],
        ['name' => 'Streaming', 'slug' => 'streaming', 'description' => 'Streaming wars, subscriptions, and media platforms.', 'topics' => [['Streaming Headlines', 'streaming news'], ['OTT Platforms', 'ott platform news'], ['Subscriber Trends', 'streaming subscriber news']]],
        ['name' => 'Movies', 'slug' => 'movies', 'description' => 'Film releases, box office, and studio activity.', 'topics' => [['Movie Headlines', 'movie news'], ['Box Office', 'box office news'], ['Studio Watch', 'film studio news']]],
        ['name' => 'Music', 'slug' => 'music', 'description' => 'Artists, streaming charts, and music business coverage.', 'topics' => [['Music Headlines', 'music news'], ['Charts Watch', 'music charts news'], ['Artist Updates', 'artist news']]],
        ['name' => 'Celebrity', 'slug' => 'celebrity', 'description' => 'Celebrity moves, interviews, and spotlight stories.', 'topics' => [['Celebrity Headlines', 'celebrity news'], ['Hollywood Watch', 'hollywood celebrity news'], ['Star Interviews', 'celebrity interview news']]],
        ['name' => 'Wellness', 'slug' => 'wellness', 'description' => 'Wellness trends, exercise, and mental health.', 'topics' => [['Wellness Headlines', 'wellness news'], ['Fitness Watch', 'fitness news'], ['Mental Health', 'mental health news']]],
        ['name' => 'Biotech', 'slug' => 'biotech', 'description' => 'Biotech companies, trials, and health innovation.', 'topics' => [['Biotech Headlines', 'biotech news'], ['Clinical Trials', 'clinical trial news'], ['Health Innovation', 'medical innovation news']]],
        ['name' => 'Public Safety', 'slug' => 'public-safety', 'description' => 'Emergency response, public safety, and incident coverage.', 'topics' => [['Public Safety Headlines', 'public safety news'], ['Emergency Response', 'emergency response news'], ['Incident Reports', 'major incident news']]],
        ['name' => 'Defense', 'slug' => 'defense', 'description' => 'Defense policy, procurement, and military updates.', 'topics' => [['Defense Headlines', 'defense news'], ['Military Watch', 'military news'], ['Security Policy', 'national security news']]],
        ['name' => 'Weather', 'slug' => 'weather', 'description' => 'Storms, forecasts, and major weather systems.', 'topics' => [['Weather Headlines', 'weather news'], ['Storm Watch', 'storm news'], ['Forecast Radar', 'forecast news']]],
        ['name' => 'Agriculture', 'slug' => 'agriculture', 'description' => 'Food systems, farming, and agri-business updates.', 'topics' => [['Agriculture Headlines', 'agriculture news'], ['Food Supply', 'food supply news'], ['Farm Markets', 'farm market news']]],
        ['name' => 'Commodities', 'slug' => 'commodities', 'description' => 'Commodity prices, metals, and global trade supply chains.', 'topics' => [['Commodities Headlines', 'commodities news'], ['Gold Market', 'gold market news'], ['Trade Supply', 'global supply chain news']]],
    ];

    public function up(): void
    {
        $now = now();
        $sortOrder = (int) DB::table('news_sections')->max('sort_order');

        foreach ($this->extraSections as $section) {
            $existingSectionId = DB::table('news_sections')->where('slug', $section['slug'])->value('id');

            if (!$existingSectionId) {
                $sortOrder++;
                $existingSectionId = DB::table('news_sections')->insertGetId([
                    'name' => $section['name'],
                    'slug' => $section['slug'],
                    'description' => $section['description'],
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                    'is_default' => false,
                    'refresh_interval_minutes' => 10,
                    'card_limit' => 6,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $topicOrder = (int) DB::table('news_topics')->where('news_section_id', $existingSectionId)->max('sort_order');

            foreach ($section['topics'] as [$name, $keyword]) {
                if (DB::table('news_topics')->where('keyword', $keyword)->exists()) {
                    continue;
                }

                $topicOrder++;
                DB::table('news_topics')->insert([
                    'news_section_id' => $existingSectionId,
                    'name' => $name,
                    'keyword' => $keyword,
                    'language' => 'en',
                    'country' => 'US',
                    'sort_order' => $topicOrder,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $slugs = array_column($this->extraSections, 'slug');
        $sectionIds = DB::table('news_sections')->whereIn('slug', $slugs)->pluck('id');

        DB::table('news_topics')->whereIn('news_section_id', $sectionIds)->delete();
        DB::table('news_sections')->whereIn('slug', $slugs)->delete();
    }
};
