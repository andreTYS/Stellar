'use client'

import Link from 'next/link'
import { ArrowRight, Sparkles, BookOpen, Users, Award } from 'lucide-react'

const stats = [
  { icon: BookOpen, value: '50+', label: 'Cursos' },
  { icon: Users,    value: '1,200+', label: 'Estudiantes' },
  { icon: Award,    value: '95%',  label: 'Satisfacción' },
]

export default function Hero() {
  return (
    <section className="relative min-h-screen flex items-center justify-center px-4 pt-16 overflow-hidden">
      {/* Nebula glow blobs */}
      <div className="absolute top-1/4 left-1/4 w-96 h-96 bg-purple-600/15 rounded-full blur-3xl pointer-events-none" />
      <div className="absolute bottom-1/4 right-1/4 w-80 h-80 bg-blue-600/15 rounded-full blur-3xl pointer-events-none" />
      <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none" />

      <div className="relative z-10 max-w-5xl mx-auto text-center">
        {/* Badge */}
        <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full glass border border-purple-500/30 text-sm text-purple-300 mb-8 animate-pulse-slow">
          <Sparkles size={14} className="text-purple-400" />
          Plataforma educativa STEAM &amp; Web
        </div>

        {/* Headline */}
        <h1 className="text-5xl sm:text-6xl lg:text-7xl font-extrabold leading-tight mb-6">
          <span className="text-white">Aprende.</span>{' '}
          <span className="gradient-text">Crea.</span>{' '}
          <span className="text-white">Innova.</span>
        </h1>

        {/* Sub-headline */}
        <p className="max-w-2xl mx-auto text-lg sm:text-xl text-white/60 leading-relaxed mb-10">
          Stellar es la plataforma educativa que te lleva más allá. Explora las ciencias, la
          tecnología, el arte y el desarrollo web con cursos interactivos diseñados para el futuro.
        </p>

        {/* CTA buttons */}
        <div className="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16">
          <Link href="/courses" className="btn-primary text-base px-8 py-4">
            Explorar cursos
            <ArrowRight size={18} />
          </Link>
          <Link href="/about" className="btn-secondary text-base px-8 py-4">
            Conoce Stellar
          </Link>
        </div>

        {/* Stats */}
        <div className="grid grid-cols-3 gap-4 max-w-lg mx-auto">
          {stats.map(({ icon: Icon, value, label }) => (
            <div key={label} className="glass rounded-2xl p-4 card-hover">
              <Icon size={20} className="text-purple-400 mx-auto mb-2" />
              <p className="text-2xl font-bold gradient-text">{value}</p>
              <p className="text-xs text-white/50 mt-1">{label}</p>
            </div>
          ))}
        </div>
      </div>

      {/* Scroll indicator */}
      <div className="absolute bottom-8 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2 text-white/30 text-xs animate-bounce">
        <span>Scroll</span>
        <div className="w-px h-8 bg-gradient-to-b from-white/30 to-transparent" />
      </div>
    </section>
  )
}
