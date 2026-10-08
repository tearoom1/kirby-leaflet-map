// dark symbols on light marker colors, white ones otherwise (as on the website)
export const contrast = (color) => {
  let hex = String(color || '#3388ff').replace('#', '')
  if (hex.length < 6) {
    hex = hex.split('').map(char => char + char).join('')
  }

  const [r, g, b] = [0, 2, 4].map(start => parseInt(hex.slice(start, start + 2), 16) / 255)

  return 0.2126 * r + 0.7152 * g + 0.0722 * b > 0.6 ? '#1d1d1d' : '#ffffff'
}
