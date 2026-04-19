import { chromium } from 'playwright'

const front = process.env.DIAG_URL || 'http://localhost:5173/hotels'
const terms = ['D', 'Par', 'lon', 'xyz']

const browser = await chromium.launch({ headless: true })
const page = await browser.newPage()

await page.goto(front, { waitUntil: 'networkidle', timeout: 60000 })
const input = page.locator('input[placeholder*="Paris"], input[placeholder*="Dubaï"], .hero-input').first()

const results = []

for (const term of terms) {
  let requestCount = 0
  const onRequest = (r) => {
    if (r.url().includes('/api/hotels/suggestions') && r.url().includes(`q=${encodeURIComponent(term)}`)) {
      requestCount += 1
    }
  }
  page.on('request', onRequest)

  await input.click()
  await input.fill('')
  await input.fill(term)

  let matchedResponse = null
  let parsedData = []
  let responseStatus = null
  try {
    matchedResponse = await page.waitForResponse(
      (res) => res.url().includes('/api/hotels/suggestions') && res.url().includes(`q=${encodeURIComponent(term)}`),
      { timeout: 3500 },
    )
    responseStatus = matchedResponse.status()
    const text = await matchedResponse.text()
    const json = JSON.parse(text)
    parsedData = Array.isArray(json) ? json : []
  } catch {
    matchedResponse = null
    parsedData = []
    responseStatus = null
  }

  if (!matchedResponse) {
    await page.waitForTimeout(1200)
  }

  const ui = await page.evaluate(() => {
    const dropdown = document.querySelector('.autocomplete-dropdown')
    const noResultsNode = dropdown?.querySelector('.dropdown-empty')

    let vueState = { found: false }
    const probeInput = document.querySelector('input[placeholder*="Paris"], input[placeholder*="Dubaï"], .hero-input')
    let probe = probeInput
    let guard = 0
    while (probe && guard < 20) {
      const vm = probe.__vueParentComponent
      if (vm?.type?.__name === 'SearchAutocomplete' || vm?.type?.name === 'SearchAutocomplete') {
        const state = vm.setupState || {}
        const suggestions = Array.isArray(state.suggestions?.value)
          ? state.suggestions.value
          : (Array.isArray(state.suggestions) ? state.suggestions : [])
        const showSuggestions = typeof state.showSuggestions?.value !== 'undefined'
          ? state.showSuggestions.value
          : !!state.showSuggestions
        vueState = {
          found: true,
          suggestionsLength: suggestions.length,
          sample: suggestions.slice(0, 3),
          showSuggestions,
        }
        break
      }
      probe = probe.parentElement
      guard += 1
    }

    return {
      dropdownInDom: !!dropdown,
      hasNoResultsNode: !!noResultsNode,
      vueState,
    }
  })

  page.off('request', onRequest)

  const labels = parsedData.map((x) => x.label || x.nom || x.ville || '').slice(0, 5)

  results.push({
    term,
    requestCount,
    responseStatus,
    responseCount: parsedData.length,
    responseLabels: labels,
    dropdownInDom: ui.dropdownInDom,
    noResultsShown: ui.hasNoResultsNode,
    vueSuggestionsLength: ui.vueState.suggestionsLength ?? -1,
    vueShowSuggestions: ui.vueState.showSuggestions ?? false,
  })
}

await input.click()
await input.fill('Du')
await page.waitForTimeout(850)
const firstSuggestion = page.locator('.autocomplete-dropdown .dropdown-item').first()
const suggestionText = await firstSuggestion.textContent()
await firstSuggestion.click()
await page.waitForTimeout(250)

const clickCase = await page.evaluate(() => {
  const input = document.querySelector('input[placeholder*="Paris"], input[placeholder*="Dubaï"], .hero-input')
  const dropdown = document.querySelector('.autocomplete-dropdown')
  const dropdownStyle = dropdown ? window.getComputedStyle(dropdown) : null

  let vueShowSuggestions = null
  let probe = input
  let guard = 0
  while (probe && guard < 20) {
    const vm = probe.__vueParentComponent
    if (vm?.type?.__name === 'SearchAutocomplete' || vm?.type?.name === 'SearchAutocomplete') {
      const state = vm.setupState || {}
      vueShowSuggestions = typeof state.showSuggestions?.value !== 'undefined'
        ? state.showSuggestions.value
        : !!state.showSuggestions
      break
    }
    probe = probe.parentElement
    guard += 1
  }

  return {
    inputValue: input ? input.value : '',
    dropdownStillVisible: !!dropdown,
    dropdownDisplay: dropdownStyle?.display || null,
    dropdownOpacity: dropdownStyle?.opacity || null,
    vueShowSuggestions,
  }
})

console.log('AUTOCOMPLETE_CASES', JSON.stringify(results, null, 2))
console.log('CLICK_CASE', JSON.stringify({ suggestionText, ...clickCase }, null, 2))

await browser.close()
