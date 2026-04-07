import Link from 'next/link'
import { Star } from 'lucide-react'

const links = {
  Plataforma: [
    { label: 'Inicio',    href: '/' },
    { label: 'Cursos',    href: '/courses' },
    { label: 'Nosotros',  href: '/about' },
    { label: 'Contacto',  href: '/#contact' },
  ],
  Categorías: [
    { label: 'Ciencias',     href: '/courses' },
    { label: 'Tecnología',   href: '/courses' },
    { label: 'Ingeniería',   href: '/courses' },
    { label: 'Arte',         href: '/courses' },
    { label: 'Matemáticas',  href: '/courses' },
    { label: 'Web',          href: '/courses' },
  ],
  Legal: [
    { label: 'Privacidad',    href: '/' },
    { label: 'Términos',      href: '/' },
    { label: 'Cookies',       href: '/' },
  ],
}

export default function Footer() {
  return (
    <footer className="relative border-t border-white/5 mt-16">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10">
          {/* Brand */}
          <div>
            <div className="flex items-center gap-2 mb-4">
              <Star size={22} className="text-purple-400 fill-current" />
              <span className="text-xl font-bold gradient-text">Stellar</span>
            </div>
            <p className="text-white/40 text-sm leading-relaxed max-w-xs">
              La plataforma educativa STEAM &amp; Web diseñada para impulsar el potencial de
              cada estudiante en Latinoamérica.
            </p>
          </div>

          {/* Link columns */}
          {Object.entries(links).map(([title, items]) => (
            <div key={title}>
              <h4 className="text-white font-semibold text-sm mb-4 uppercase tracking-wider">{title}</h4>
              <ul className="flex flex-col gap-2">
                {items.map(({ label, href }) => (
                  <li key={label}>
                    <Link
                      href={href}
                      className="text-white/40 text-sm hover:text-white/80 transition-colors duration-200"
                    >
                      {label}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>

        {/* Bottom bar */}
        <div className="mt-12 pt-8 border-t border-white/5 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-white/30">
          <p>© 2026 Stellar Education. Todos los derechos reservados.</p>
          <p>Hecho con amor para la comunidad educativa latinoamericana</p>
        </div>
      </div>
    </footer>
  )
}
