# Mosab Qafya Detector Architecture

The package separates qafya analysis into small layers so poem-level decisions do not keep accumulating inside `PoemQafyaDetector`.

## Layers

1. **Word detection layer**
   - `WordQafyaDetector`
   - `Detection/ComponentDetector`
   - `Classification/RawiEligibilityPolicy`

   This layer analyzes a single ending and reports local qafya components: `rawi`, `radf`, `wasl`, `khurooj`, `taasis`, `dakhiil`, pattern, signatures, and trace.

2. **Poem ending collection layer**
   - `Analysis/PoemEndingCollector`

   This layer splits the poem into paired hemistichs and sends the configured positions (`ajz` only by default, or `sadr + ajz`) to the word detector.

3. **Poem-level laws / rules layer**
   - `Analysis/PoemLevelRulePipeline`
   - `Analysis/Rules/FinalAlefLikeRule`
   - `Analysis/Rules/FinalYaaRule`
   - `Analysis/Rules/CollectiveWawAlefRule`
   - `Analysis/Rules/HaaSuffixRule`
   - `Analysis/Rules/TaaMarbutaAndDisplayRule`

   This layer applies poem-context rules after the word detector. It handles weak and special endings such as final `ا/ى`, final `ي`, collective `وا`, suffixes like `ها/يه`, and contextual `ة/ت` resolution.

4. **Poem clustering / arbitration layer**
   - `Analysis/PoemClusterAnalyzer`
   - `Analysis/PublicDominantQafyaNormalizer`

   This layer groups by canonical rawi identity, decides whether the dominant qafya is authoritative, preserves hamza-seat surface patterns, and prevents unstable `taasis/dakhiil/radf` from polluting the public dominant result.

5. **Response presentation layer**
   - `Analysis/PoemAnalysisPresenter`
   - `Data/*`

   This layer builds the public payload and typed response objects.

## Public result fields

The poem-level response keeps these fields stable for consumers:

- `rawi`
- `rawi_haraka`
- `mujra`
- `radf`
- `taasis`
- `dakhiil`
- `wasl`
- `khurooj`
- `segment`
- `pattern`
- `signature`
- `clusters`
- `endings`
- `defects`
- `sanad`

## Important policy

Public poem-level qafya identity is grouped by **canonical rawi**. Component variants remain visible in `endings`, `defects`, and `sanad`, but they do not split a poem into multiple public qafyas unless the rawi itself changes.

Examples:

- `تعاقبي / مثالبي / مكاسبي` => public `rawi=ب`, `pattern=بي`; unstable `taasis/dakhiil` stay in ending details.
- `مالئا / هادئا / بادئا` => public `rawi=ء`, `pattern=ئا`; hamza identity is canonical, but the visible seat remains in the pattern.
- `البهيِّ / السميِّ / العليِّ / الشهيِّ` => public `rawi=ي`, `pattern=ي`; final yā is promoted by poem context.
