'use client'

import { useState, useEffect } from 'react'
import Link from 'next/link'
import { Menu, X, Star } from 'lucide-react'

const links = [
  { href: '/',          label: 'Inicio' },
  { href: '/courses',   label: 'Cursos' },
  { href: '/about',     label: 'Nosotros' },
  { href: '/#contact',  label: 'Contacto' },
]

export default function Navbar() {
  const [open, setOpen] = useState(false)
  const [scrolled, setScrolled] = useState(false)

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 40)
    window.addEventListener('scroll', onScroll)
    return () => window.removeEventListener('scroll', onScroll)
  }, [])

  return (
    <header
      className={`fixed top-0 inset-x-0 z-50 transition-all duration-300 ${
        scrolled ? 'glass-strong shadow-lg shadow-black/30' : 'bg-transparent'
      }`}
    >
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between h-16">
          {/* Logo */}
          <Link href="/" className="flex items-center gap-2 group">
            <div className="relative">
              <Star
                size={28}
                className="text-purple-400 group-hover:text-cyan-400 transition-colors duration-300 fill-current"
              />
              <div className="absolute inset-0 bg-purple-400/30 blur-md rounded-full group-hover:bg-cyan-400/30 transition-colors duration-300" />
            </div>
            <span className="text-xl font-bold gradient-text">Stellar</span>
          </Link>

          {/* Desktop nav */}
          <nav className="hidden md:flex items-center gap-1">
            {links.map((l) => (
              <Link
                key={l.href}
                href={l.href}
                className="px-4 py-2 rounded-lg text-sm font-medium text-white/70 hover:text-white hover:bg-white/5 transition-all duration-200"
              >
                {l.label}
              </Link>
            ))}
            <Link href="/courses" className="ml-4 btn-primary text-sm px-5 py-2">
              Empezar gratis
            </Link>
          </nav>

          {/* Mobile toggle */}
          <button
            className="md:hidden p-2 rounded-lg text-white/70 hover:text-white hover:bg-white/5 transition-colors"
            onClick={() => setOpen(!open)}
            aria-label="Menú"
          >
            {open ? <X size={22} /> : <Menu size={22} />}
          </button>
        </div>
      </div>

      {/* Mobile menu */}
      {open && (
        <div className="md:hidden glass-strong border-t border-white/5">
          <nav className="max-w-7xl mx-auto px-4 py-4 flex flex-col gap-1">
            {links.map((l) => (
              <Link
                key={l.href}
                href={l.href}
                onClick={() => setOpen(false)}
                className="px-4 py-3 rounded-lg text-sm font-medium text-white/80 hover:text-white hover:bg-white/5 transition-colors"
              >
                {l.label}
              </Link>
            ))}
            <Link href="/courses" onClick={() => setOpen(false)} className="mt-2 btn-primary text-sm justify-center">
              Empezar gratis
            </Link>
          </nav>
        </div>
      )}
    </header>
  )
}
