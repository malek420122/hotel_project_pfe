import { chromium } from 'playwright'

const front = process.env.DIAG_URL || 'http://localhost:5173/hotels'

const browser = await chromium.launch({ headless: true })
const page = await browser.newPage()

const errors = []
const requestUrls = []
const responses = []
const failedRequests = []

page.on('console', (m) => {
  if (m.type() === 'error') errors.push(m.text())
})

page.on('request', (r) => {
  const url = r.url()
  if (url.includes('/api/hotels/suggestions')) {
    requestUrls.push(url)
  }
})

page.on('response', async (r) => {
  const url = r.url()
  if (!url.includes('/api/hotels/suggestions')) return

  let body = ''
  try {
    body = await r.text()
  } catch {
    body = ''
  }

  responses.push({
    url,
    status: r.status(),
    body: body.slice(0, 800),
  })
})

page.on('requestfailed', (r) => {
  const url = r.url()
  if (!url.includes('/api/hotels/suggestions')) return
  failedRequests.push({ url, error: r.failure()?.errorText || 'unknown' })
})

await page.goto(front, { waitUntil: 'networkidle', timeout: 60000 })

const input = page.locator('input[placeholder*="Paris"], input[placeholder*="Dubaï"], .hero-input').first()
await input.click()
await input.fill('Du')
await page.waitForTimeout(900)

const dropdownDiagnostics = await page.evaluate(() => {
  const dropdown = document.querySelector('.autocomplete-dropdown')
  const input = document.querySelector('input[placeholder*="Paris"], input[placeholder*="Dubaï"], .hero-input')
  const style = dropdown ? window.getComputedStyle(dropdown) : null
  const rect = dropdown ? dropdown.getBoundingClientRect() : null

  const ancestors = []
  let el = dropdown ? dropdown.parentElement : null
  let steps = 0
  while (el && steps < 12) {
    const cs = window.getComputedStyle(el)
    ancestors.push({
      tag: el.tagName.toLowerCase(),
      className: el.className,
      overflow: cs.overflow,
      overflowX: cs.overflowX,
      overflowY: cs.overflowY,
      position: cs.position,
      zIndex: cs.zIndex,
    })
    el = el.parentElement
    steps += 1
  }

  let vueState = null
  let probe = input
  let guard = 0
  while (probe && guard < 20) {
    const vm = probe.__vueParentComponent
    if (vm?.type?.__name === 'SearchAutocomplete' || vm?.type?.name === 'SearchAutocomplete') {
      const state = vm.setupState || {}
      const rawSuggestions = state.suggestions
      const rawShow = state.showSuggestions
      const rawLoading = state.suggestionsLoading
      const suggestionsArray = Array.isArray(rawSuggestions?.value)
        ? rawSuggestions.value
        : (Array.isArray(rawSuggestions) ? rawSuggestions : [])
      const showValue = typeof rawShow?.value !== 'undefined' ? rawShow.value : rawShow
      const loadingValue = typeof rawLoading?.value !== 'undefined' ? rawLoading.value : rawLoading

      vueState = {
        found: true,
        suggestionsLength: suggestionsArray.length,
        suggestionsSample: suggestionsArray.slice(0, 3),
        showSuggestions: !!showValue,
        loading: !!loadingValue,
      }
      break
    }
    probe = probe.parentElement
    guard += 1
  }

  if (!vueState) {
    vueState = { found: false }
  }

  return {
    inDom: !!dropdown,
    style: style
      ? {
          display: style.display,
          visibility: style.visibility,
          opacity: style.opacity,
          zIndex: style.zIndex,
          position: style.position,
        }
      : null,
    rect,
    ancestors,
    vueState,
  }
})

console.log('REQUEST_COUNT', requestUrls.length)
requestUrls.forEach((u, i) => console.log(`REQUEST_${i + 1} ${u}`))

console.log('RESPONSE_COUNT', responses.length)
responses.forEach((r, i) => console.log(`RESPONSE_${i + 1} ${JSON.stringify(r)}`))

console.log('FAILED_REQUEST_COUNT', failedRequests.length)
failedRequests.forEach((r, i) => console.log(`FAILED_REQUEST_${i + 1} ${JSON.stringify(r)}`))

console.log('DROPDOWN_DIAG', JSON.stringify(dropdownDiagnostics))
console.log('CONSOLE_ERROR_COUNT', errors.length)
errors.forEach((e, i) => console.log(`CONSOLE_ERROR_${i + 1} ${e}`))

await browser.close()
