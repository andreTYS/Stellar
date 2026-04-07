import type { Metadata } from 'next'
import './globals.css'
import Navbar from '@/components/Navbar'
import Starfield from '@/components/Starfield'

export const metadata: Metadata = {
  title: 'Stellar — Educación STEAM & Web',
  description:
    'Plataforma educativa de excelencia para aprender Ciencias, Tecnología, Ingeniería, Arte, Matemáticas y Desarrollo Web con una experiencia inmersiva.',
  keywords: ['educación', 'STEAM', 'web', 'ciencias', 'tecnología', 'programación', 'matemáticas'],
  authors: [{ name: 'Stellar Education' }],
  openGraph: {
    title: 'Stellar — Educación STEAM & Web',
    description: 'Aprende. Crea. Innova. La plataforma educativa del futuro.',
    type: 'website',
  },
}

export default function RootLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <html lang="es" className="scroll-smooth">
      <body className="antialiased">
        <Starfield />
        <div className="relative z-10">
          <Navbar />
          <main>{children}</main>
        </div>
      </body>
    </html>
  )
}
