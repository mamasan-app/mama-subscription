import { Button } from '@/components/ui/button';
import Logo from '../../assets/img/logo-a.png';

export const Hero = () => {
  return (
    <div className="relative bg-white px-6 py-2">
      <div className="absolute right-4 top-4 flex gap-2">
        <Button
          variant="outline"
          className="border-primary text-primary hover:bg-primary/10"
          onClick={() => {}}
        >
          Iniciar Sesión
        </Button>
        <Button
          className="bg-primary hover:bg-primary-dark text-white"
          onClick={() => {}}
        >
          Registrarse
        </Button>
      </div>
      <div className="text-center">
        <div className="mb-0 flex justify-center">
          <img src={Logo} alt="Cobra Fácil Logo" className="h-auto w-64" />
        </div>
        <div className="mt-0">
          <h1 className="from-primary to-primary-dark mb-4 bg-gradient-to-r bg-clip-text text-5xl font-bold text-transparent">
            Cobra más fácil, más rápido y sin complicaciones
          </h1>
          <p className="mx-auto mb-8 max-w-2xl text-xl text-gray-600">
            Automatiza tus cobranzas y olvídate de las complicaciones. La
            solución perfecta para gimnasios, colegios y condominios.
          </p>
          <div className="flex justify-center gap-4">
            <Button
              className="bg-primary hover:bg-primary-dark px-8 py-6 text-lg text-white"
              onClick={() => {}}
            >
              Comenzar Ahora
            </Button>
            <Button
              variant="outline"
              className="border-primary text-primary hover:bg-primary/10 px-8 py-6 text-lg"
            >
              Solicitar Demo
            </Button>
          </div>
        </div>
      </div>
    </div>
  );
};
